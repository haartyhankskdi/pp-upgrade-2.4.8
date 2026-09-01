<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_AdminActionLog
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
namespace Bss\AdminActionLog\Convert;

class FineDiff
{
    /**
     * @var string[]
     */
    private $granularityStack = [" \t.\n\r"];

    /**
     * @var array
     */
    private $edits = [];

    /**
     * @var string $fromText
     */
    private $fromText;

    /**
     * @var mixed $lastEdit
     */
    private $lastEdit;
    /**
     * @var int $fromOffset
     */
    private $fromOffset;

    /**
     * @var
     */
    private $toText;

    /**
     * @var
     */
    private $stackpointer;

    /**
     * @var FineDiffCopyOpFactory
     */
    private $diffCopyFactory;

    /**
     * @var FineDiffDeleteOpFactory
     */
    private $diffDeleteFactory;

    /**
     * @var FineDiffInsertOpFactory
     */
    private $diffInsertFactory;

    /**
     * @var FineDiffReplaceOpFactory
     */
    private $diffReplaceFactory;

    /**
     * FineDiff constructor.
     * @param FineDiffCopyOpFactory $diffCopyFactory
     * @param FineDiffDeleteOpFactory $diffDeleteFactory
     * @param FineDiffInsertOpFactory $diffInsertFactory
     * @param FineDiffReplaceOpFactory $diffReplaceFactory
     */
    public function __construct(
        \Bss\AdminActionLog\Convert\FineDiffCopyOpFactory $diffCopyFactory,
        \Bss\AdminActionLog\Convert\FineDiffDeleteOpFactory $diffDeleteFactory,
        \Bss\AdminActionLog\Convert\FineDiffInsertOpFactory $diffInsertFactory,
        \Bss\AdminActionLog\Convert\FineDiffReplaceOpFactory $diffReplaceFactory
    ) {
        $this->diffCopyFactory = $diffCopyFactory;
        $this->diffDeleteFactory = $diffDeleteFactory;
        $this->diffInsertFactory = $diffInsertFactory;
        $this->diffReplaceFactory = $diffReplaceFactory;
    }

    /**
     * Get Ops
     *
     * @return array
     */
    public function getOps()
    {
        return $this->edits;
    }

    /**
     * Get Opcodes
     *
     * @return string
     */
    public function getOpcodes()
    {
        $opcodes = [];
        foreach ($this->edits as $edit) {
            $opcodes[] = $edit->getOpcode();
        }
        return implode('', $opcodes);
    }

    /**
     * Get Diff Opcodes
     *
     * @param $from
     * @param $to
     * @return string
     */
    public function getDiffOpcodes($from, $to)
    {
        $this->fromText = $from;
        $this->doDiff($from, $to);
        return $this->getOpcodes();
    }

    /**
     * _renderDiffToHTML
     *
     * @param $to
     * @param $opcodes
     * @return mixed
     */
    public function _renderDiffToHTML($to, $opcodes)
    {
        $opcodesLen = strlen($opcodes);
        $fromOffset = $opcodesOffset = 0;
        $html = $to;
        while ($opcodesOffset <  $opcodesLen) {
            $opcode = substr($opcodes, $opcodesOffset, 1);
            $opcodesOffset++;
            $n = intval(substr($opcodes, $opcodesOffset));
            if ($n) {
                $opcodesOffset += strlen(strval($n));
            } else {
                $n = 1;
            }

            if ($opcode === 'i') {
                $html_i = $this->renderDiffToHTMLFromOpcode('i', $opcodes, $opcodesOffset + 1, $n);
                $html_r = substr($opcodes, $opcodesOffset + 1, $n);
                $html = str_replace($html_r, $html_i, $html);
                $opcodesOffset += 1 + $n;
            }
        }
        return $html;
    }

    /**
     * Render Diff To HTML
     *
     * @param $from
     * @param $opcodes
     * @return string
     */
    public function renderDiffToHTML($from, $opcodes)
    {
        $opcodesLen = strlen($opcodes);
        $fromOffset = $opcodesOffset = 0;
        $html = '';
        while ($opcodesOffset <  $opcodesLen) {
            $opcode = substr($opcodes, $opcodesOffset, 1);
            $opcodesOffset++;
            $n = intval(substr($opcodes, $opcodesOffset));
            if ($n) {
                $opcodesOffset += strlen(strval($n));
            } else {
                $n = 1;
            }
            if ($opcode === 'c') {
                $html .= $this->renderDiffToHTMLFromOpcode('c', $from, $fromOffset, $n);
                $fromOffset += $n;
            } elseif ($opcode === 'd') {
                $html .= $this->renderDiffToHTMLFromOpcode('d', $from, $fromOffset, $n);
                $fromOffset += $n;
            } elseif ($opcode !== 'd' && strlen($from) > 1) {
                $html = $from;
                break;
            }
        }
        return $html;
    }

    /**
     * Do Diff
     *
     * @param $fromText
     * @param $toText
     */
    public function doDiff($fromText, $toText)
    {
        $this->lastEdit = false;
        $this->stackpointer = 0;
        $this->fromText = $fromText;
        $this->fromOffset = 0;
        if (empty($this->granularityStack)) {
            return;
        }
        $this->processGranularity($fromText, $toText);
    }

    /**
     * Process Granularity
     *
     * @param $fromSegment
     * @param $toSegment
     */
    public function processGranularity($fromSegment, $toSegment)
    {
        $delimiters = $this->granularityStack[$this->stackpointer++];
        $has_next_stage = $this->stackpointer < count($this->granularityStack);
        foreach ($this->doFragmentDiff($fromSegment, $toSegment, $delimiters) as $fragmentEdit) {
            $this->edits[] = $this->lastEdit = $fragmentEdit;
            $this->fromOffset += $fragmentEdit->getFromLen();
        }
        $this->stackpointer--;
    }

    /**
     * Do Fragment Diff
     *
     * @param $fromText
     * @param $toText
     * @param $delimiters
     * @return array
     */
    public function doFragmentDiff($fromText, $toText, $delimiters)
    {
        if (empty($delimiters)) {
            return $this->doCharDiff($fromText, $toText);
        }

        $result = [];

        $fromTextLen = strlen($fromText);
        $toTextLen = strlen($toText);
        $fromFragments = $this->extractFragments($fromText, $delimiters);
        $toFragments = $this->extractFragments($toText, $delimiters);

        $jobs = [[0, $fromTextLen, 0, $toTextLen]];

        while ($job = array_pop($jobs)) {

            // get the segments which must be diff'ed
            list($fromSegmentStart, $fromSegmentEnd, $toSegmentStart, $toSegmentEnd) = $job;

            // catch easy cases first
            $fromSegmentLength = $fromSegmentEnd - $fromSegmentStart;
            $toSegmentLength = $toSegmentEnd - $toSegmentStart;
            if (!$fromSegmentLength || !$toSegmentLength) {
                if ($fromSegmentLength) {
                    $result[$fromSegmentStart * 4] = $this->diffDeleteFactory->create(['len' => $fromSegmentLength]);
                } elseif ($toSegmentLength) {
                    $result[$fromSegmentStart * 4 + 1] = $this
                        ->diffInsertFactory->create(['text' => substr($toText, $toSegmentStart, $toSegmentLength)]);
                }
                continue;
            }

            $bestCopyLength = 0;

            $fromBaseFragmentIndex = $fromSegmentStart;

            $simpleLoop = $this
                ->simpleLoop($fromSegmentStart, $fromSegmentEnd, $fromFragments, $toFragments, $toSegmentStart, $toSegmentEnd, $toTextLen, $fromSegmentLength);

            $bestCopyLength = $simpleLoop['best_copy_length'];
            $bestFromStart = $simpleLoop['best_from_start'];
            $bestToStart = $simpleLoop['best_to_start'];
            $fromSegmentStart = $simpleLoop['from_segment_start'];
            $toSegmentStart = $simpleLoop['to_segment_start'];
            $toSegmentEnd = $simpleLoop['to_segment_end'];

            if ($bestCopyLength) {
                $jobs[] = [$fromSegmentStart, $bestFromStart, $toSegmentStart, $bestToStart];
                $result[$bestFromStart * 4 + 2] = $this->diffCopyFactory->create(['len' => $bestCopyLength]);
                $jobs[] = [$bestFromStart + $bestCopyLength, $fromSegmentEnd, $bestToStart + $bestCopyLength, $toSegmentEnd];
            } else {
                $result[$fromSegmentStart * 4] = $this->diffReplaceFactory
                    ->create(['fromLen' => $fromSegmentLength,'text' => substr($toText, $toSegmentStart, $toSegmentLength)]);
            }
        }

        ksort($result, SORT_NUMERIC);
        return array_values($result);
    }

    /**
     * Simple Loop
     *
     * @param $fromSegmentStart
     * @param $fromSegmentEnd
     * @param $fromFragments
     * @param $toFragments
     * @param $toSegmentStart
     * @param $toSegmentEnd
     * @param $toTextLen
     * @param $fromSegmentLength
     * @return array
     */
    private function simpleLoop($fromSegmentStart, $fromSegmentEnd, $fromFragments, $toFragments, $toSegmentStart, $toSegmentEnd, $toTextLen, $fromSegmentLength)
    {
        $bestFromStart = $bestToStart = null;
        $bestCopyLength = 0;

        $fromBaseFragmentIndex = $fromSegmentStart;

        while ($fromBaseFragmentIndex < $fromSegmentEnd) {
            $fromBaseFragment = $fromFragments[$fromBaseFragmentIndex];
            $fromBaseFragmentLength = strlen($fromBaseFragment);
            $toAllFragmentIndices = array_keys($toFragments, $fromBaseFragment, true);

            // get only indices which falls within current segment
            if ($toSegmentStart > 0 || $toSegmentEnd < $toTextLen) {
                $toFragmentIndices = $this->_simpleLoop($toAllFragmentIndices, $toSegmentStart, $toSegmentEnd);
            } else {
                $toFragmentIndices = $toAllFragmentIndices;
            }
            // iterate through collected indices
            foreach ($toFragmentIndices as $toBaseFragmentIndex) {
                $fragmentIndexOffset = $fromBaseFragmentLength;
                // iterate until no more match
                for (;;) {
                    $fragmentFromIndex = $fromBaseFragmentIndex + $fragmentIndexOffset;
                    if ($fragmentFromIndex >= $fromSegmentEnd) {
                        break;
                    }
                    $fragmentToIndex = $toBaseFragmentIndex + $fragmentIndexOffset;
                    if (($fragmentToIndex >= $toSegmentEnd)
                           || ($fromFragments[$fragmentFromIndex] !== $toFragments[$fragmentToIndex])
                        ) {
                        break;
                    }
                    $fragment_length = strlen($fromFragments[$fragmentFromIndex]);
                    $fragmentIndexOffset += $fragment_length;
                }
                if ($fragmentIndexOffset > $bestCopyLength) {
                    $bestCopyLength = $fragmentIndexOffset;
                    $bestFromStart = $fromBaseFragmentIndex;
                    $bestToStart = $toBaseFragmentIndex;
                }
            }
            $fromBaseFragmentIndex += strlen($fromBaseFragment);

            if (($bestCopyLength >= $fromSegmentLength / 2)
                     || ($fromBaseFragmentIndex + $bestCopyLength >= $fromSegmentEnd)
                    ) {
                break;
            }
        }
        return ['best_copy_length' => $bestCopyLength,
                'best_from_start' => $bestFromStart,
                'best_to_start' => $bestToStart,
                'from_segment_start' => $fromSegmentStart,
                'to_segment_start' => $toSegmentStart,
                'to_segment_end' => $toSegmentEnd
            ];
    }

    /**
     * Simple Loop
     *
     * @param $toAllFragmentIndices
     * @param $toSegmentStart
     * @param $toSegmentEnd
     * @return array
     */
    private function _simpleLoop($toAllFragmentIndices, $toSegmentStart, $toSegmentEnd)
    {
        $toFragmentIndices = [];
        foreach ($toAllFragmentIndices as $to_fragment_index) {
            if ($to_fragment_index < $toSegmentStart) {
                continue;
            }
            if ($to_fragment_index >= $toSegmentEnd) {
                break;
            }
            $toFragmentIndices[] = $to_fragment_index;
        }
        return $toFragmentIndices;
    }

    /**
     * Do Char Diff
     *
     * @param $fromText
     * @param $toText
     * @return array
     */
    private function doCharDiff($fromText, $toText)
    {
        $result = [];
        $jobs = [[0, strlen($fromText), 0, strlen($toText)]];
        while ($job = array_pop($jobs)) {
            // get the segments which must be diff'ed
            list($fromSegmentStart, $fromSegmentEnd, $toSegmentStart, $toSegmentEnd) = $job;
            $fromSegmentLen = $fromSegmentEnd - $fromSegmentStart;
            $toSegmentLen = $toSegmentEnd - $toSegmentStart;

            // catch easy cases first
            if (!$fromSegmentLen || !$toSegmentLen) {
                $result = $result +
                    $this->getResultFirst($fromSegmentLen, $fromSegmentStart, $toText, $toSegmentStart, $toSegmentLen);
                continue;
            }
            $fromCopyStart = null;
            if ($fromSegmentLen >= $toSegmentLen) {
                $copyLen = $toSegmentLen;
                while ($copyLen) {
                    $toCopyStart = $toSegmentStart;
                    $toCopyStartMax = $toSegmentEnd - $copyLen;
                    while ($toCopyStart <= $toCopyStartMax) {
                        $fromCopyStart = strpos(substr($fromText, $fromSegmentStart, $fromSegmentLen), substr($toText, $toCopyStart, $copyLen));
                        if ($fromCopyStart !== false) {
                            $fromCopyStart += $fromSegmentStart;
                            break 2;
                        }
                        $toCopyStart++;
                    }
                    $copyLen--;
                }
            } else {
                $copyLen = $fromSegmentLen;
                while ($copyLen) {
                    $fromCopyStart = $fromSegmentStart;
                    $fromCopyStart_max = $fromSegmentEnd - $copyLen;
                    while ($fromCopyStart <= $fromCopyStart_max) {
                        $toCopyStart = strpos(substr($toText, $toSegmentStart, $toSegmentLen), substr($fromText, $fromCopyStart, $copyLen));
                        if ($toCopyStart !== false) {
                            $toCopyStart += $toSegmentStart;
                            break 2;
                        }
                        $fromCopyStart++;
                    }
                    $copyLen--;
                }
            }

            $result = $result +
                $this->getResultLast($copyLen, $fromCopyStart, $fromSegmentStart, $fromSegmentLen, $toText, $toSegmentStart, $toSegmentLen);
        }

        ksort($result, SORT_NUMERIC);
        return array_values($result);
    }

    /**
     * Get Result First
     *
     * @param $fromSegmentLen
     * @param $fromSegmentStart
     * @param $toText
     * @param $toSegmentStart
     * @param $toSegmentLen
     * @return array
     */
    private function getResultFirst($fromSegmentLen, $fromSegmentStart, $toText, $toSegmentStart, $toSegmentLen)
    {
        $result = [];
        if ($fromSegmentLen) {
            $result[$fromSegmentStart * 4 + 0] = $this->diffDeleteFactory->create(['len' => $fromSegmentLen]);
        } elseif ($toSegmentLen) {
            $result[$fromSegmentStart * 4 + 1] =
                $this->diffInsertFactory->create(['text' => substr($toText, $toSegmentStart, $toSegmentLen)]);
        }
        return $result;
    }

    /**
     * Get Result Last
     *
     * @param $copyLen
     * @param $fromCopyStart
     * @param $fromSegmentStart
     * @param $fromSegmentLen
     * @param $toText
     * @param $toSegmentStart
     * @param $toSegmentLen
     * @return array
     */
    private function getResultLast($copyLen, $fromCopyStart, $fromSegmentStart, $fromSegmentLen, $toText, $toSegmentStart, $toSegmentLen)
    {
        $result = [];
        if ($copyLen) {
            $result[$fromCopyStart * 4 + 2] = $this->diffCopyFactory->create(['len' => $copyLen]);
        } else {
            $result[$fromSegmentStart * 4] =
                $this->diffReplaceFactory->create(['fromLen' => $fromSegmentLen,'text' => substr($toText, $toSegmentStart, $toSegmentLen)]);
        }
        return $result;
    }

    /**
     * Extract Fragments
     *
     * @param $text
     * @param $delimiters
     * @return array
     */
    private function extractFragments($text, $delimiters)
    {
        if (empty($delimiters)) {
            $chars = str_split($text, 1);
            $chars[strlen($text)] = '';
            return $chars;
        }
        $fragments = [];
        $start = $end = 0;
        for (;;) {
            $end += strcspn($text, $delimiters, $end);
            $end += strspn($text, $delimiters, $end);
            if ($end === $start) {
                break;
            }
            $fragments[$start] = substr($text, $start, $end - $start);
            $start = $end;
        }
        $fragments[$start] = '';
        return $fragments;
    }

    /**
     * Render Diff To HTML From Opcode
     *
     * @param $opcode
     * @param $from
     * @param $fromOffset
     * @param $from_len
     * @return string
     */
    private function renderDiffToHTMLFromOpcode($opcode, $from, $fromOffset, $from_len)
    {
        $html = '';
        if ($opcode === 'c') {
            $html .= htmlentities(substr($from, $fromOffset, $from_len));
        } elseif ($opcode === 'd') {
            $deletion = substr($from, $fromOffset, $from_len);

            if (strcspn($deletion, " \n\r") === 0) {
                $deletion = str_replace(["\n","\r"], ['\n','\r'], $deletion);
            }
            $html .= '<del>' . htmlentities($deletion) . '</del>';
        } else {
            $html .= '<ins>' . htmlentities(substr($from, $fromOffset, $from_len)) . '</ins>';
        }

        return $html;
    }
}
