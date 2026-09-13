<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-core
 * @version   1.7.20
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



declare(strict_types=1);

namespace Mirasvit\Core\Ai\Service;

/**
 * Builds the CURLOPT_HTTPHEADER list for every AI provider client.
 *
 * cURL reads only the *values* of CURLOPT_HTTPHEADER, so the list it receives must consist of
 * well-formed "Name: Value" strings. Provider clients hold their defaults in that list form, while
 * AbstractAiService::buildRequestHeaders() hands them an associative array, so merging the two with
 * a plain array_merge() left the caller entries string-keyed and cURL emitted their values with no
 * header name (e.g. "Mirasvit-Core/1.0" instead of "User-Agent: Mirasvit-Core/1.0").
 */
class HeaderService
{
    /**
     * Merge client default headers with caller-supplied headers into a cURL header list.
     *
     * Either side may be given in cURL-list form ("Name: Value") or associative form
     * ("Name" => "Value"); every returned entry is a well-formed "Name: Value" string, and a caller
     * header overrides a default of the same (case-insensitive) name instead of being appended
     * alongside it.
     *
     * @param array $default  client default headers
     * @param array $override caller headers (take precedence by header name)
     *
     * @return string[] cURL header lines
     */
    public function normalize(array $default, array $override = []): array
    {
        return array_values(array_merge(
            $this->indexByName($default),
            $this->indexByName($override)
        ));
    }

    /**
     * Index a header array by lower-cased header name.
     *
     * Every entry is normalized to a "Name: Value" string. Accepts both associative
     * ("Name" => "Value") and list ("Name: Value") entries.
     *
     * @param array $headers headers in either associative or cURL-list form
     *
     * @return array<string, string> map of lower-cased header name => "Name: Value"
     */
    private function indexByName(array $headers): array
    {
        $byName = [];

        foreach ($headers as $key => $value) {
            $line = is_string($key)
                ? trim($key) . ': ' . $value
                : trim((string)$value);

            $name = trim(explode(':', $line, 2)[0]);

            if ($name === '') {
                continue;
            }

            $byName[strtolower($name)] = $line;
        }

        return $byName;
    }
}
