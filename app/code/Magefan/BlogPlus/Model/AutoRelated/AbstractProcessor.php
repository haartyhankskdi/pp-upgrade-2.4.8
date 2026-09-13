<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogPlus\Model\AutoRelated;

use \Magefan\BlogPlus\Model\Config;

/**
 * Class AbstractProcessor
 */
abstract class AbstractProcessor
{

    /**
     * @var \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory
     */
    protected $postCollectionFactory;

    /**
     * @var \Magefan\Blog\Model\ResourceModel\Post
     */
    protected $resource;

    /**
     * @var \Magefan\BlogPlus\Model\Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $autoRelatedTable;

    /**
     * @var string
     */
    protected $autoRelatedLogTable;

    /**
     * @var int
     */
    protected $limit = 20;

    /**
     * BlogPlus constructor.
     * @param \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory
     * @param \Magefan\Blog\Model\ResourceModel\Post $resource
     * @param Config $config
     */
    public function __construct(
        \Magefan\Blog\Model\ResourceModel\Post\CollectionFactory $postCollectionFactory,
        \Magefan\Blog\Model\ResourceModel\Post $resource,
        Config $config
    ) {
        $this->postCollectionFactory = $postCollectionFactory;
        $this->resource = $resource;
        $this->config = $config;
    }

    /**
     * Generate auto generated items
     */
    public function execute(): void
    {
        if ($this->config->isEnabled()) {
            if ($this->autoRelatedEnabled()) {
                $this->processAutoRelated();
            } else {
                $this->cleanAutoRelated();
            }
        }
    }

    /**
     * Retrieve tru if can generate auto related items
     *
     * @return bool
     */
    protected function autoRelatedEnabled()
    {
        return true;
    }

    /**
     * Processes auto-related posts by identifying and saving related items based on custom logic.
     *
     * @return void
     */
    protected function processAutoRelated()
    {
        $collection = $this->postCollectionFactory->create();
        $resource = $collection->getResource();
        $connection = $resource->getConnection();

        $collection->getSelect()->joinLeft(
            ['log_table' => $resource->getTable($this->autoRelatedLogTable)],
            'main_table.post_id = log_table.post_id',
            []
        );

        $collection
            ->addFieldToFilter(
                [
                    'log_table.created_at',
                    'log_table.created_at',
                ],
                [
                    ['null' => true],
                    ['lteq' => date('Y-m-d', time() - 7 * 86400)],
                ]
            )
            ->addFieldToFilter('is_active', 1);

        $collection->setPageSize(100);

        $autoRelatedTable = $resource->getTable($this->autoRelatedTable);
        $autoRelatedLogTable = $resource->getTable($this->autoRelatedLogTable);

        foreach ($collection as $post) {
            /* Delete old Auto Related */
            $this->cleanAutoRelated($post->getId());
            /* Get autorelated IDs */
            $autorelatedIds = $this->getAutoRelatedIds($post);
            $relatedIds = $this->getRelatedIds($post);
            $autorelatedIds = array_diff($autorelatedIds, $relatedIds);
            /* Save autorelated items */
            if (count($autorelatedIds)) {
                $data = [];
                $position = 10000;
                foreach ($autorelatedIds as $autorelatedId) {
                    $position++;
                    $data [] = [
                        'post_id' => $post->getId(),
                        'related_id' => $autorelatedId,
                        'auto_related' => 1,
                        'position' => $position
                    ];
                }
                $connection->insertMultiple($autoRelatedTable, $data);
            }

            /* Save post log */
            $logData = ['post_id' => $post->getId(), 'created_at' => date('Y-m-d')];
            $connection->insertMultiple($autoRelatedLogTable, $logData);
        }
    }

    /**
     * Remove old autorelated items
     *
     * @param int|string|null $postId
     */
    protected function cleanAutoRelated($postId = null)
    {
        $resource = $this->resource;
        $connection = $resource->getConnection();

        $where = ['auto_related = 1'];
        if (null !== $postId) {
            $where['post_id = ?'] = $postId;
        }
        $connection->delete(
            $resource->getTable($this->autoRelatedTable),
            $where
        );

        $where = [];
        if (null !== $postId) {
            $where['post_id = ?'] = $postId;
        }
        $connection->delete(
            $resource->getTable($this->autoRelatedLogTable),
            $where
        );
    }

    /**
     * Get auto related items
     *
     * @param \Magefan\Blog\Model\Post $post
     * @return mixed
     */
    abstract protected function getAutoRelatedIds(
        \Magefan\Blog\Model\Post $post
    );

    /**
     * Extracts keywords from the provided HTML content by removing tags, ignored words, and short tokens
     *
     * @param string $html
     * @param int $max
     * @return string
     */
    protected function extractKeywords($html, $max = 20)
    {
        if (!$html) {
            return '';
        }

        $ignoredWords = $this->getIgnoredWords();

        $text = preg_replace('/&(#x[0-9a-f]+|#[0-9]+|[a-zA-Z]+);/', '', strip_tags($html));

        $softhyphen = html_entity_decode('&#173;', ENT_NOQUOTES, 'UTF-8'); //phpcs:ignore
        $text = str_replace($softhyphen, '', $text);
        if (function_exists('mb_split')) {
            $wordlist = mb_split('\s*\W+\s*', mb_strtolower($text));
        } else {
            $wordlist = preg_split('%\s*\W+\s*%', strtolower($text));
        }
        $tokens = array_count_values($wordlist);
        if (is_array($ignoredWords)) {
            foreach ($ignoredWords as $word) {
                unset($tokens[$word]);
            }
        }

        foreach (array_keys($tokens) as $word) {
            if (!is_string($word)) {
                unset($tokens[$word]);
            } elseif (function_exists('mb_strlen')) {
                if (mb_strlen($word) < 2) {
                    unset($tokens[$word]);
                } elseif (strlen($word) < 2) {
                    unset($tokens[$word]);
                }
            }
        }

        arsort($tokens, SORT_NUMERIC);

        $types = array_keys($tokens);

        if (count($types) > $max) {
            $types = array_slice($types, 0, $max);
        }

        return implode(' ', $types);
    }

    /**
     * Get ignored words
     *
     * @return array
     */
    protected function getIgnoredWords()
    {
        // phpcs:disable Generic.Files.LineLength
        return ['', 'a', 'an', 'the', 'and', 'of', 'i', 'to', 'is', 'in',
            'with', 'for', 'as', 'that', 'on', 'at', 'this', 'my', 'was',
            'our', 'it', 'you', 'we', '1', '2', '3', '4', '5', '6', '7', '8',
            '9', '0', '10', 'about', 'after', 'all', 'almost', 'along', 'also',
            'amp', 'another', 'any', 'are', 'area', 'around', 'available', 'back',
            'be', 'because', 'been', 'being', 'best', 'better', 'big', 'bit', 'both',
            'but', 'by', 'c', 'came', 'can', 'capable', 'control', 'could', 'course', 'd',
            'dan', 'day', 'decided', 'did', 'didn', 'different', 'div', 'do', 'doesn', 'don',
            'down', 'drive', 'e', 'each', 'easily', 'easy', 'edition', 'end', 'enough', 'even',
            'every', 'example', 'few', 'find', 'first', 'found', 'from', 'get', 'go', 'going', 'good',
            'got', 'gt', 'had', 'hard', 'has', 'have', 'he', 'her', 'here', 'how', 'if', 'into', 'isn',
            'just', 'know', 'last', 'left', 'li', 'like', 'little', 'll', 'long', 'look', 'lot', 'lt', 'm',
            'made', 'make', 'many', 'mb', 'me', 'menu', 'might', 'mm', 'more', 'most', 'much', 'name', 'nbsp',
            'need', 'new', 'no', 'not', 'now', 'number', 'off', 'old', 'one', 'only', 'or', 'original', 'other',
            'out', 'over', 'part', 'place', 'point', 'pretty', 'probably', 'problem', 'put', 'quite', 'quot', 'r',
            're', 'really', 'results', 'right', 's', 'same', 'saw', 'see', 'set', 'several', 'she', 'sherree', 'should',
            'since', 'size', 'small', 'so', 'some', 'something', 'special', 'still', 'stuff', 'such', 'sure', 'system', 't',
            'take', 'than', 'their', 'them', 'then', 'there', 'these', 'they', 'thing', 'things', 'think', 'those', 'though',
            'through', 'time', 'today', 'together', 'too', 'took', 'two', 'up', 'us', 'use', 'used', 'using', 've', 'very',
            'want', 'way', 'well', 'went', 'were', 'what', 'when', 'where', 'which', 'while', 'white', 'who', 'will', 'would',
            'your',

            'имат', 'май', 'скоро', 'голям', 'имам', 'понеже', 'сякаш', 'сред', 'бе', 'стане', 'голяма', 'супер', 'затова', 'всяка',
            'могат', 'макар', 'тоя', 'нещата', 'бях', 'някои', 'значи', 'друго', 'някакъв', 'иска', 'ето', 'било', 'наистина', 'никой',
            'имаш', 'себе', 'вярно', 'къде', 'съвсем', 'никога', 'ами', 'едва', 'пъти', 'неща', 'върху', 'тогава', 'колко', 'например',
            'тук', 'можеш', 'тези', 'та', 'бъде', 'дали', 'мога', 'теб', 'вид', 'често', 'даже', 'друг', 'нали', 'повечето', 'такива',
            'място', 'особено', 'иначе', 'всъщност', 'направо', 'примерно', 'знае', 'голямата', 'смисъл', 'имаше', 'едни', 'понякога',
            'из', 'своя', 'искам', 'веднъж', 'явно', 'ей', 'какви', 'естествено', 'изобщо', 'почва', 'остава', 'зад', 'някъде', 'отново',
            'плюс', 'някак', 'цяла', 'нов', 'бих', 'другите', 'коя', 'разни', 'що', 'накрая', 'тъй', 'някакви', 'някаква', 'въобще', 'първо',
            'такъв', 'правят', 'цял', 'вместо', 'бяха', 'тая', 'тия', 'нито', 'име', 'сам', 'км', 'де', 'били', 'била', 'общо', 'искаш', 'сте',
            'чак', 'абсолютно', 'успява', 'цена', 'някоя', 'колкото', 'идва', 'кво', 'цялата', 'лв', 'други', 'поне', 'как', 'какво', 'ги', 'когато',
            'към', 'аз', 'още', 'нещо', 'или', 'съм', 'той', 'няма', 'което', 'ни', 'вече', 'им', 'все', 'дори', 'защото', 'едно', 'преди', 'малко',
            'ме', 'беше', 'при', 'със', 'една', 'обаче', 'трябва', 'която', 'така', 'един', 'са', 'като', 'най', 'това', 'ще', 'но', 'по', 'че', 'се',
            'да', 'не', 'от', 'си', 'за', 'му', 'го', 'до', 'след', 'който', 'които', 'има', 'може', 'само', 'през', 'ти', 'много', 'ми', 'ли', 'те',
            'ако', 'всички', 'във', 'прави', 'две', 'смее', 'бил', 'под', 'тази', 'освен', 'между', 'пред', 'казва', 'то', 'път', 'кой', 'после', 'около',
            'над', 'въпреки', 'щото', 'винаги', 'срещу', 'част', 'ви', 'два', 'нея', 'почти', 'заради', 'защо', 'където', 'късно', 'мен', 'нас', 'ден',
            'повече', 'толкова', 'според', 'този', 'както', 'докато', 'пък', 'ама', 'без', 'би', 'всичко', 'просто', 'тя', 'него', 'става', 'пак', 'тях',
            'сме', 'някой', 'доста', 'също', 'ние', 'нищо', 'точно', 'сега', 'добре', 'всеки', 'няколко', 'там', 'на',

            '', 'a', 'aby', 'aj', 'ale', 'anebo', 'ani', 'aniž', 'ano', 'asi', 'žz', 'ba', 'bez', 'bude', 'budem', 'budeš', 'by', 'byl', 'byla', 'byli', 'bylo',
            'být', 'či', 'článek', 'článku', 'články', 'co', 'com', 'což', 'cz', 'další', 'design', 'dnes', 'do', 'email', 'ho', 'i', 'jak', 'jaké jako', 'je',
            'jeho', 'jej', 'její', 'jejich', 'jen', 'ještě', 'jenž', 'ji', 'jiné', 'již', 'jsem', 'jseš', 'jsi', 'jsme', 'jsou', 'jste', 'k', 'kam', 'kde', 'kdo',
            'když', 'ke', 'která', 'které', 'kteří', 'kterou', 'který', 'ku', 'má', 'máte', 'mě', 'mezi', 'mi', 'mít', 'mne', 'mně', 'mnou', 'můj', 'může', 'my',
            'na', 'nad', 'nám', 'napište', 'náš', 'nás', 'naši', 'ne', 'nebo', 'neboť', 'nechť', 'nejsou', 'není', 'net', 'než', 'ni', 'nic', 'nové', 'nový', 'nýbrž',
            'o', 'od', 'ode', 'on', 'org', 'pak', 'po', 'pod', 'podle', 'pokud', 'pouze', 'právě', 'před', 'přes', 'při', 'pro', 'proč', 'proto', 'protože', 'první',
            'ptá', 're', 's', 'se', 'si', 'sice', 'spol', 'strana', 'své', 'svá', 'svůj', 'svých', 'svým', 'svými', 'ta', 'tak', 'také', 'takže', 'tamhle', 'tato',
            'tedy', 'téma', 'tě', 'té', 'ten', 'tedy', 'tento', 'této', 'tím ', 'tímto', 'tipy', 'to', 'tohle', 'toho', 'tohoto', 'tom', 'tomto', 'tomuto', 'totiž',
            'tu', 'tudíž', 'tuto', 'tvůj', 'ty', 'tyto', 'u', 'už', 'v', 'vám', 'vás', 'váš', 'vaše', 've', 'vedle', 'více', 'však', 'všechen', 'vy', 'vždyt', 'z',
            'za', 'zda', 'zde', 'že', 'zpět', 'zpráva', 'zprávy',

            'der','die','und','in','den','von','zu','das','mit','sich','des','auf','für','ist','im','dem','nicht','ein','eine','als','auch','es','an','werden','aus',
            'er','hat','daß','dass','sie','nach','wird','bei','einer','um','am','sind','noch','wie','einem','über','einen','so','zum','war','haben','nur','oder','aber',
            'vor','zur','bis','mehr','durch','man','sein','wurde','sei','in','Prozent','hatte','kann','gegen','vom','können','schon','wenn','habe','seine','ihre','dann',
            'unter','wir','soll','ich','eines','Jahr','zwei','Jahren','diese','dieser','wieder','keine','Uhr','seiner','worden','will','zwischen','immer','Millionen','was',
            'sagte','gibt','alle','diesem','seit','muß','muss','wurden','beim','doch','jetzt','waren','drei','Jahre','neue','neuen','damit','bereits','da','ihr','seinen',
            'müssen','ab','ihrer','ohne','sondern','selbst','ersten','nun','etwa',

            'a', 'abandonner', 'abattre', 'abord', 'aborder', 'abri', 'absence', 'absolu', 'absolument', 'accent', 'accepter', 'accompagner', 'accomplir', 'accord',
            'accorder', 'accrocher', 'accueillir', 'accuser', 'acheter', 'achever', 'acte', 'action', 'admettre', 'adresser', 'affaire', 'affirmer', 'afin', 'agent',
            'agir', 'agiter', 'ah', 'ai', 'aide', 'aider', 'aile', 'ailleurs', 'aimer', 'ainsi', 'air', 'ajouter', 'aller', 'allumer', 'alors', 'amener', 'ami', 'amour',
            'amuser', 'an', 'ancien', 'anglais', 'angoisse', 'animal', 'animer', 'annoncer', 'année', 'apercevoir', 'apparaître', 'apparence', 'appartement', 'appartenir',
            'appel', 'appeler', 'apporter', 'apprendre', 'approcher', 'approuver', 'appuyer', 'après', 'arbre', 'argent', 'arme', 'armer', 'armée', 'arracher', 'arriver',
            'arrivée', 'arrière', 'arrêter', 'art', 'article', 'as', 'aspect', 'asseoir', 'assez', 'assister', 'assurer', 'attacher', 'attaquer', 'atteindre', 'attendre',
            'attente', 'attention', 'attirer', 'attitude', 'au', 'aucun', 'aujourd\'hui', 'aujourd’hui', 'auparavant', 'auprès', 'auquel', 'aussi', 'aussitôt', 'autant',
            'auteur', 'autorité', 'autour', 'autre', 'autrefois', 'autrement', 'autres', 'aux', 'auxquelles', 'auxquels', 'avance', 'avancer', 'avant', 'avec', 'avenir',
            'aventure', 'avis', 'avoir', 'avouer', 'baisser', 'banc', 'bande', 'barbe', 'bas', 'bataille', 'battre', 'beau', 'beaucoup', 'beauté', 'beaux', 'besoin', 'bien',
            'bientôt', 'billet', 'blanc', 'bleu', 'blond', 'boire', 'bois', 'bon', 'bonheur', 'bons', 'bord', 'bouche', 'bout', 'branche', 'bras', 'briller', 'briser', 'bruit',
            'brusquement', 'brûler', 'bureau', 'but', 'bête', 'cabinet', 'cacher', 'calme', 'calmer', 'camarade', 'campagne', 'capable', 'car', 'caractère', 'caresser', 'carte',
            'cas', 'casser', 'cause', 'causer', 'ce', 'ceci', 'cela', 'celui', 'cent', 'centre', 'cependant', 'cercle', 'certain', 'certainement', 'certes', 'cerveau', 'ces',
            'cesse', 'cesser', 'cet', 'cette', 'chacun', 'chair', 'chaise', 'chaleur', 'chambre', 'champ', 'chance', 'changement', 'changer', 'chant', 'chanter', 'chaque',
            'charge', 'charger', 'chasse', 'chasser', 'chat', 'chaud', 'chaîne', 'chef', 'chemin', 'chemise', 'cher', 'chercher', 'cheval', 'cheveu', 'chez', 'chien', 'chiffre',
            'choisir', 'choix', 'chose', 'chute', 'ci', 'ciel', 'cinq', 'cinquante', 'circonstance', 'clair', 'claire', 'classe', 'clef', 'coin', 'colline', 'colon', 'colère',
            'combat', 'combien', 'commander', 'comme', 'commencement', 'commencer', 'comment', 'commun', 'compagnie', 'compagnon', 'complet', 'complètement', 'composer',
            'comprendre', 'compte', 'compter', 'conclure', 'condamner', 'condition', 'conduire', 'confiance', 'confier', 'confondre', 'connaissance', 'connaître', 'conscience',
            'conseil', 'consentir', 'considérer', 'construire', 'consulter', 'contenir', 'content', 'contenter', 'continuer', 'contraire', 'contre', 'convenir', 'conversation',
            'corde', 'corps', 'cou', 'couche', 'coucher', 'couler', 'couleur', 'coup', 'couper', 'cour', 'courage', 'courant', 'courir', 'cours', 'course', 'court', 'couvrir',
            'coûter', 'craindre', 'crainte', 'creuser', 'cri', 'crier', 'crise', 'croire', 'croiser', 'croix', 'cruel', 'créer', 'cuisine', 'curieux', 'curiosité', 'céder',
            'côte', 'côté', 'cœur', 'd\'un', 'd\'une', 'dame', 'danger', 'dangereux', 'dans', 'danser', 'davantage', 'de', 'debout', 'dedans', 'dehors', 'delà', 'demain',
            'demande', 'demander', 'demeurer', 'demi', 'dent', 'depuis', 'dernier', 'derrière', 'des', 'descendre', 'desquelles', 'desquels', 'dessiner', 'dessous', 'dessus',
            'deux', 'devant', 'devenir', 'deviner', 'devoir', 'dieu', 'difficile', 'différent', 'digne', 'dimanche', 'dire', 'direction', 'diriger', 'discours', 'discussion',
            'discuter', 'disparaître', 'disposer', 'distance', 'distinguer', 'divers', 'dix', 'docteur', 'doigt', 'dominer', 'donc', 'donner', 'dont', 'dormir', 'dos', 'double',
            'doucement', 'douceur', 'douleur', 'doute', 'douter', 'doux', 'douze', 'drame', 'dresser', 'droit', 'droite', 'drôle', 'du', 'duquel', 'dur', 'durant', 'durer', 'dès',
            'début', 'déchirer', 'décider', 'déclarer', 'découvrir', 'décrire', 'défaut', 'défendre', 'dégager', 'déjà', 'départ', 'dépasser', 'déposer', 'désert', 'désespoir',
            'désigner', 'désir', 'désirer', 'désormais', 'détacher', 'détail', 'détourner', 'détruire', 'd’un', 'd’une', 'eau', 'eaux', 'effacer', 'effet', 'effort', 'eh', 'elle',
            'elles', 'embrasser', 'emmener', 'emparer', 'empire', 'employer', 'emporter', 'empêcher', 'en', 'encore', 'endormir', 'endroit', 'enfance', 'enfant', 'enfermer',
            'enfin', 'enfoncer', 'engager', 'enlever', 'ennemi', 'ensemble', 'ensuite', 'entendre', 'entier', 'entourer', 'entraîner', 'entre', 'entrer', 'entretenir', 'entrée',
            'envelopper', 'envie', 'environ', 'envoyer', 'erreur', 'escalier', 'espace', 'espoir', 'esprit', 'espèce', 'espérer', 'essayer', 'essuyer', 'est', 'et', 'etc', 'euh',
            'eux', 'examiner', 'excepté', 'exemple', 'exiger', 'existence', 'exister', 'explication', 'expliquer', 'exposer', 'expression', 'exprimer', 'expérience',
            'extraordinaire', 'extrême', 'exécuter', 'face', 'facile', 'faible', 'faim', 'faire', 'fais', 'fait', 'falloir', 'famille', 'fatigue', 'fatiguer', 'faute',
            'fauteuil', 'faux', 'faveur', 'façon', 'femme', 'fenêtre', 'fer', 'ferme', 'fermer', 'feu', 'feuille', 'fidèle', 'fier', 'figure', 'figurer', 'fil', 'fille', 'fils',
            'fin', 'fine', 'finir', 'fixe', 'fixer', 'flamme', 'fleur', 'flot', 'foi', 'fois', 'folie', 'fonction', 'fond', 'fonder', 'force', 'forcer', 'forme', 'former', 'fort',
            'fortune', 'forêt', 'fou', 'foule', 'frais', 'franc', 'franchir', 'français', 'françois', 'frapper', 'froid', 'front', 'fruit', 'frère', 'fuir', 'fumer', 'fumée',
            'fusil', 'fête', 'gagner', 'garde', 'garder', 'garçon', 'gauche', 'genou', 'genre', 'gens', 'geste', 'glace', 'glisser', 'gloire', 'goutte', 'gouvernement', 'goût',
            'grain', 'grand', 'grandir', 'grave', 'gris', 'gros', 'groupe', 'grâce', 'gré', 'guerre', 'guider', 'guère', 'général', 'habiller', 'habitant', 'habiter', 'habitude',
            'haine', 'hasard', 'haut', 'haute', 'hauteur', 'haïr', 'hein', 'herbe', 'heure', 'heureux', 'heurter', 'hier', 'histoire', 'hiver', 'homme', 'honneur', 'honte',
            'horizon', 'hormis', 'hors', 'huit', 'humain', 'humide', 'hésiter', 'hôtel', 'ici', 'idée', 'ignorer', 'il', 'illusion', 'ils', 'image', 'imaginer', 'immense',
            'immobile', 'immédiatement', 'importance', 'important', 'importer', 'imposer', 'impossible', 'impression', 'incapable', 'inconnu', 'indiquer', 'inquiéter',
            'inquiétude', 'inspirer', 'installer', 'instant', 'instinct', 'instrument', 'intelligence', 'intention', 'interroger', 'interrompre', 'intéresser', 'intérieur',
            'intérêt', 'inutile', 'inventer', 'inviter', 'jamais', 'jambe', 'jardin', 'jaune', 'je', 'jeter', 'jeu', 'jeune', 'jeunesse', 'joie', 'joindre', 'joli', 'joue',
            'jouer', 'jour', 'journal', 'journée', 'juge', 'juger', 'juif', 'jusqu\'au', 'jusqu\'aux', 'jusqu\'à', 'jusque', 'jusqu’au', 'jusqu’aux', 'jusqu’à', 'juste',
            'justice', 'l\'un', 'l\'une', 'la', 'laquelle', 'large', 'larme', 'le', 'lendemain', 'lentement', 'lequel', 'les', 'lesquelles', 'lesquels', 'lettre', 'leur',
            'leurs', 'lever', 'leçon', 'liberté', 'libre', 'lien', 'lier', 'lieu', 'ligne', 'lire', 'lisser', 'lit', 'livre', 'livrer', 'loi', 'loin', 'long', 'longtemps',
            'lors', 'lorsque', 'loup', 'lourd', 'lueur', 'lui', 'lumière', 'lune', 'lutte', 'lutter', 'là', 'lèvre', 'léger', 'l’un', 'l’une', 'ma', 'machine', 'madame',
            'magnifique', 'main', 'maintenant', 'maintenir', 'mais', 'maison', 'mal', 'malade', 'maladie', 'malgré', 'malheur', 'manche', 'manger', 'manier', 'manquer',
            'marchand', 'marche', 'marcher', 'marché', 'mari', 'mariage', 'marier', 'marquer', 'masse', 'matin', 'matière', 'mauvais', 'maître', 'me', 'meilleur', 'meilleure',
            'meilleures', 'meilleurs', 'membre', 'menacer', 'mener', 'mensonge', 'mentir', 'mer', 'mes', 'mesure', 'mettre', 'midi', 'mien', 'miens', 'mieux', 'milieu',
            'militaire', 'mille', 'million', 'mince', 'mine', 'ministre', 'minute', 'miser', 'mode', 'moi', 'moindre', 'moins', 'mois', 'moitié', 'moment', 'mon', 'monde',
            'monsieur', 'montagne', 'monter', 'montrer', 'morceau', 'mort', 'mot', 'mourir', 'mouvement', 'moyen', 'muet', 'mur', 'musique', 'médecin', 'mémoire', 'mériter',
            'métier', 'mêler', 'même', 'naissance', 'nation', 'nature', 'naturel', 'naturellement', 'naître', 'ne', 'nerveux', 'neuf', 'nez', 'ni', 'noir', 'noire', 'nom',
            'nombre', 'nombreux', 'nommer', 'non', 'nord', 'nos', 'note', 'notre', 'nourrir', 'nous', 'nouveau', 'noyer', 'nu', 'nuage', 'nuit', 'nul', 'nécessaire', 'nôtre',
            'nôtres', 'objet', 'obliger', 'observer', 'obtenir', 'obéir', 'occasion', 'occuper', 'odeur', 'oeil', 'officier', 'offrir', 'oh', 'oiseau', 'ombre', 'on', 'oncle',
            'or', 'ordre', 'oreille', 'oser', 'ou', 'oublier', 'ouh', 'oui', 'ouvert', 'ouvrage', 'ouvrir', 'où', 'page', 'pain', 'paix', 'palais', 'papa', 'papier', 'paquet',
            'par', 'paraître', 'parce', 'parcourir', 'pareil', 'parent', 'parer', 'parfait', 'parfaitement', 'parfois', 'parler', 'parmi', 'parole', 'part', 'partager', 'parti',
            'particulier', 'partie', 'partir', 'partout', 'parvenir', 'pas', 'passage', 'passer', 'passion', 'passé', 'patron', 'paupière', 'pauvre', 'payer', 'pays', 'paysage',
            'paysan', 'peau', 'peine', 'pencher', 'pendant', 'pendre', 'penser', 'pensée', 'perdre', 'perdu', 'permettre', 'personnage', 'personne', 'perte', 'peser', 'petit',
            'peu', 'peuple', 'peur', 'phrase', 'pied', 'pierre', 'piquer', 'pire', 'pires', 'pitié', 'pièce', 'place', 'placer', 'plaindre', 'plaine', 'plaire', 'plaisir',
            'plan', 'planche', 'plante', 'plein', 'pleurer', 'plier', 'plonger', 'pluie', 'plus', 'plusieurs', 'plutôt', 'poche', 'poids', 'point', 'pointe', 'poitrine',
            'police', 'politique', 'pont', 'port', 'porte', 'porter', 'portier', 'poser', 'position', 'possible', 'posséder', 'poste', 'pour', 'pourquoi', 'poursuivre',
            'pourtant', 'pousser', 'poussière', 'pouvoir', 'poète', 'poésie', 'premier', 'prendre', 'presque', 'presser', 'preuve', 'prier', 'prince', 'principe', 'printemps',
            'prison', 'prix', 'prière', 'probablement', 'problème', 'prochain', 'produire', 'professeur', 'profiter', 'profond', 'profondément', 'projet', 'promener',
            'promettre', 'prononcer', 'propos', 'proposer', 'propre', 'protéger', 'prouver', 'près', 'précieux', 'précipiter', 'précis', 'précéder', 'préférer', 'préparer',
            'présence', 'présent', 'présenter', 'président', 'prétendre', 'prévenir', 'prévoir', 'prêt', 'prêter', 'public', 'puis', 'puisque', 'puissance', 'puissant', 'pur',
            'père', 'pénétrer', 'qualité', 'quand', 'quant', 'quarante', 'quart', 'quartier', 'quatre', 'que', 'quel', 'quelle', 'quelles', 'quelqu\'un', 'quelque', 'quelqu’un',
            'quels', 'question', 'queue', 'qui', 'quinze', 'quitter', 'quoi', 'quoique', 'race', 'raconter', 'rage', 'raison', 'ramasser', 'ramener', 'rang', 'rapide',
            'rapidement', 'rappeler', 'rapport', 'rapporter', 'rare', 'rassurer', 'rayon', 'recevoir', 'recherche', 'recommencer', 'reconnaître', 'recueillir', 'reculer',
            'redevenir', 'refuser', 'regard', 'regarder', 'regretter', 'rejeter', 'rejoindre', 'relation', 'relever', 'religion', 'remarquer', 'remercier', 'remettre',
            'remonter', 'remplacer', 'remplir', 'rencontre', 'rencontrer', 'rendre', 'renoncer', 'rentrer', 'renverser', 'repas', 'reposer', 'repousser', 'reprendre', 'reproche',
            'représenter', 'respect', 'respecter', 'respirer', 'ressembler', 'reste', 'rester', 'retenir', 'retirer', 'retomber', 'retour', 'retourner', 'retrouver', 'revenir',
            'revoir', 'riche', 'rideau', 'rien', 'rire', 'risquer', 'robe', 'roche', 'rocher', 'roi', 'roman', 'rompre', 'rond', 'rose', 'rouge', 'rouler', 'route', 'rue',
            'ruine', 'règle', 'réalité', 'réclamer', 'réduire', 'réel', 'réflexion', 'réfléchir', 'régulier', 'répandre', 'répondre', 'réponse', 'répéter', 'réserver',
            'résistance', 'résister', 'résoudre', 'résultat', 'réunir', 'réussir', 'réveiller', 'révolution', 'révéler', 'rêve', 'rêver', 'rôle', 'sa', 'sable', 'sac', 'saint',
            'saisir', 'saison', 'salle', 'saluer', 'salut', 'sang', 'sans', 'santé', 'satisfaire', 'sauf', 'sauter', 'sauvage', 'sauver', 'savoir', 'science', 'scène', 'se',
            'sec', 'second', 'seconde', 'secours', 'secret', 'secrétaire', 'seigneur', 'sein', 'selon', 'semaine', 'semblable', 'sembler', 'semer', 'sens', 'sentier',
            'sentiment', 'sentir', 'sept', 'serrer', 'service', 'servir', 'ses', 'seuil', 'seul', 'seulement', 'si', 'sien', 'siens', 'signe', 'signer', 'signifier',
            'silence', 'silencieux', 'simple', 'simplement', 'situation', 'six', 'siècle', 'siège', 'social', 'société', 'soi', 'soin', 'soir', 'soirée', 'soit', 'sol',
            'soldat', 'soleil', 'solitude', 'sombre', 'somme', 'sommeil', 'sommet', 'son', 'songer', 'sonner', 'sorte', 'sortir', 'sou', 'souci', 'soudain', 'souffler',
            'souffrance', 'souffrir', 'souhaiter', 'soulever', 'soumettre', 'source', 'sourd', 'sourire', 'sous', 'soutenir', 'souvenir', 'souvent', 'spectacle', 'subir',
            'succès', 'sueur', 'suffire', 'suite', 'suivant', 'suivre', 'sujet', 'supporter', 'supposer', 'supérieur', 'sur', 'surprendre', 'surtout', 'surveiller', 'système',
            'séparer', 'sérieux', 'sûr', 'ta', 'table', 'tache', 'taille', 'taire', 'tandis', 'tant', 'tantôt', 'tapis', 'tard', 'te', 'tel', 'telle', 'tellement', 'telles',
            'tels', 'temps', 'tempête', 'tendre', 'tenir', 'tenter', 'terme', 'terminer', 'terrain', 'terre', 'terreur', 'terrible', 'tes', 'théâtre', 'tien', 'tiens', 'tirer',
            'titre', 'toi', 'toile', 'toit', 'tombe', 'tomber', 'ton', 'toucher', 'toujours', 'tour', 'tourner', 'tous', 'tout', 'toute', 'trace', 'tracer', 'train', 'trait',
            'traiter', 'tranquille', 'transformer', 'travail', 'travailler', 'travers', 'traverser', 'traîner', 'trembler', 'trente', 'triste', 'trois', 'troisième', 'tromper',
            'trop', 'trou', 'troubler', 'trouver', 'très', 'trésor', 'tu', 'tuer', 'type', 'tâche', 'témoin', 'tête', 'tôt', 'un', 'une', 'unique', 'usage', 'user', 'va', 'vague',
            'vaincre', 'vais', 'valeur', 'valoir', 'vas', 'vaste', 'veille', 'veiller', 'vendre', 'venir', 'vent', 'ventre', 'verre', 'vers', 'verser', 'vert', 'victime', 'vide',
            'vie', 'vieil', 'vieillard', 'viens', 'vient', 'vieux', 'vif', 'village', 'ville', 'vin', 'vingt', 'violence', 'violent', 'visage', 'visible', 'vision', 'visite',
            'visiter', 'vite', 'vivant', 'vivre', 'voici', 'voie', 'voile', 'voilà', 'voir', 'voisin', 'voiture', 'voix', 'vol', 'voler', 'volonté', 'vos', 'votre', 'vouloir',
            'vous', 'voyage', 'voyager', 'vrai', 'vraiment', 'vue', 'véritable', 'vérité', 'vêtement', 'vêtir', 'vôtre', 'vôtres', 'y', 'yeux', 'à', 'âge', 'âgé', 'âme', 'ça',
            'écarter', 'échapper', 'éclairer', 'éclat', 'éclater', 'école', 'écouter', 'écraser', 'écrire', 'égal', 'également', 'élever', 'éloigner', 'élément', 'émotion',
            'énergie', 'énorme', 'épais', 'épaule', 'époque', 'épreuve', 'éprouver', 'établir', 'étage', 'étaler', 'état', 'éteindre', 'étendre', 'étendue', 'éternel', 'étoile',
            'étonner', 'étouffer', 'étrange', 'étranger', 'étroit', 'étude', 'étudier', 'été', 'évidemment', 'éviter', 'événement', 'être', 'île', 'œuvre',

            'il', 'di', 'e', 'a', 'un', 'in', 'che', 'non', 'ma', 'come', 'su', 'mi', 'anche', 'o', 'io', 'se', 'perché', 'li', 'ci', 'ne', 'lei', 'ancora',
            'tu', 'lui', 'senza', 'bene', 'cui', 'chi', 'già', 'dopo', 'uno', 'noi', 'dove', 'qui', 'no', 'allora', 'tra',
            'vi', 'ora', 'fra', 'prima', 'forse', 'sì', 'sotto', 'voi', 'fino', 'oggi', 'quasi', 'pure', 'egli', 'mentre', 'contro', 'invece', 'esso', 'là', 'però', 'né',
            'subito', 'verso', 'ciò', 'ecco', 'loro', 'essa', 'fuori', 'meno', 'adesso', 'niente', 'cioè', 'male', 'nulla', 'ah', 'oh', 'quindi', 'appena', 'insieme',
            'dunque', 'dentro', 'durante', 'almeno', 'secondo', 'anzi', 'oramai', 'oltre', 'intorno', 'sopra', 'dietro', 'ieri', 'davvero', 'lì', 'qualcuno', 'avanti',
            'assai', 'presto', 'qua', 'domani', 'circa', 'giù', 'soprattutto', 'nemmeno', 'grazie', 'tuttavia', 'appunto', 'neppure', 'eh', 'veramente', 'tardi', 'insomma',
            'soltanto', 'infatti', 'qualcosa', 'apesso', 'accordo', 'presso', 'intanto', 'lungo', 'neanche', 'piuttosto', 'stasera', 'perciò', 'naturalmente', 'accanto',
            'eppure', 'eccetera', 'finalmente', 'infine', 'poiché', 'comunque', 'dinanzi', 'abbastanza', 'peccato', 'certamente', 'coloro', 'attorno', 'magari', 'oppure',
            'inoltre', 'indietro', 'addosso', 'addirittura', 'finché', 'perfino', 'affatto', 'stamattina', 'completamente', 'probabilmente', 'sino', 'chissà', 'ognuno', 'entro',

            'il', 'di', 'e', 'a', 'un', 'in', 'che', 'non', 'ma', 'come', 'su', 'mi', 'anche', 'o', 'io', 'se', 'perché', 'li', 'ci', 'ne', 'lei', 'ancora',
            'tu', 'lui', 'senza', 'bene', 'cui', 'chi', 'già', 'dopo', 'uno', 'noi', 'dove', 'qui', 'no',
            'allora', 'tra', 'vi', 'ora', 'fra', 'prima', 'forse', 'sì', 'sotto', 'voi', 'fino', 'oggi', 'quasi', 'pure', 'egli', 'mentre', 'contro', 'invece', 'esso',
            'là', 'però', 'né', 'subito', 'verso', 'ciò', 'ecco', 'loro', 'essa', 'fuori', 'meno', 'adesso', 'niente', 'cioè', 'male', 'nulla', 'ah', 'oh', 'quindi', 'appena',
            'insieme', 'dunque', 'dentro', 'durante', 'almeno', 'secondo', 'anzi', 'oramai', 'oltre', 'intorno', 'sopra', 'dietro', 'ieri', 'davvero', 'lì', 'qualcuno', 'avanti',
            'assai', 'presto', 'qua', 'domani', 'circa', 'giù', 'soprattutto', 'nemmeno', 'grazie', 'tuttavia', 'appunto', 'neppure', 'eh', 'veramente', 'tardi', 'insomma',
            'soltanto', 'infatti', 'qualcosa', 'apesso', 'accordo', 'presso', 'intanto', 'lungo', 'neanche', 'piuttosto', 'stasera', 'perciò', 'naturalmente', 'accanto', 'eppure',
            'eccetera', 'finalmente', 'infine', 'poiché', 'comunque', 'dinanzi', 'abbastanza', 'peccato', 'certamente', 'coloro', 'attorno', 'magari', 'oppure', 'inoltre', 'indietro',
            'addosso', 'addirittura', 'finché', 'perfino', 'affatto', 'stamattina', 'completamente', 'probabilmente', 'sino', 'chissà', 'ognuno', 'entro',

            'een', 'de', 'het', 'of', 'in', 'naar', 'op', 'is', 'met', 'voor', 'als', 'dat', 'onder', 'dit', 'mijn', 'mij', 'was', 'ons', 'jij', 'zij', 'wij', 'over', 'na',
            'alle', 'bijna', 'langs', 'ook', 'ander', 'enig', 'enige', 'zijn', 'ben', 'bent', 'beschikbaar', 'terug', 'omdat', 'geweest',
            'doordat', 'beste', 'beter', 'groot', 'klein', 'beiden', 'maar', 'c', 'kom', 'komen','kan','kunnen','konden', 'controleren', 'zouden', 'zou', 'cursus', 'd', 'dan', 'dag',
            'beslissen', 'deden', 'deed', 'doet', 'verschillend', 'doe', 'niet', 'nog', 'onder', 'rijden', 'e', 'elke', 'gemakkelijk', 'makkie', 'editie', 'versie', 'einde', 'genoeg',
            'even', 'elke', 'voorbeeld', 'enkele', 'vinden', 'eerste', 'gevonden', 'vind', 'vindt', 'van', 'krijg', 'krijgen', 'gaan', 'ga', 'goed', 'snap', 'begrijpen', 'heb',
            'hebben', 'had', 'hard', 'heeft', 'hij', 'hem', 'haar', 'hier', 'hoe', 'niet', 'wel', 'weten', 'laatste', 'links', 'rechts', 'leuk', 'klein', 'lang', 'kijk', 'kijken',
            'veel', 'weinig', 'maken', 'maak', 'gemaakt', 'menu', 'misschien', 'wellicht', 'meer', 'minder',  'naam', 'nbsp', 'nodig', 'nieuw', 'nee', 'ja', 'niet', 'nu',
            'nummer', 'uit', 'oud', 'een', 'alleen', 'originineel', 'ander', 'andere', 'anders', 'haar', 'over', 'deel', 'plek', 'plek', 'mooi', 'waarschijnlijk', 'probleem',
            'plaats', 'ongeveer', 'gezegde', 're', 'werkelijk', 'uitkomst', 'plus', 'min','klopt', 's', 'zelfde','dezelfde', 'zag', 'zien', 'zie', 'verschillende','zou',
            'zouden', 'since', 'maat', 'kleine', 'zo', 'sommige', 'iets', 'speciaal', 'stil','nog', 'dingen', 'zoals', 'natuurlijk', 'systeem', 't', 'nemen','neem', 'dan', 'hun',
            'hen', 'daarna', 'er', 'deze', 'dingen', 'ding', 'denk', 'denken', 'zulke', 'moeilijk', 'door','doorheen', 'doordat','omdat', 'tijd', 'vandaag', 'samen', 'ook', 'nam',
            'twee', 'gebruiken','gebruik', 'gebruikt', 'gebruikte', 'zeer', 'erg', 'want', 'manier', 'nou', 'nu', 'geweest', 'waar', 'wat', 'wanneer', 'waar', 'welke', 'ondertussen',
            'terwijl', 'wit','zwart','wie','wil', 'zou','zouden', 'jou', 'jouw', 'haar', 'van','mensen', 'man', 'vrouw', 'persoon', 'goed', 'slecht', 'heel','later', 'eerder',
            'nieuw', 'moet', 'moeten', 'mogen', 'mag', 'zegt', 'zeggen', 'zei', 'zeg', 'maak', 'maken', 'maakte', 'gemaakt', 'doe', 'doen', 'deed', 'deden', 'konden', 'word', 'wordt',
            'werd', 'werden', 'ja', 'nee','uh', 'die','naar', 'je', 'maar','jaar','moet', 'aleen',

            'a', 'aby', 'acz', 'aczkolwiek', 'ale', 'ależ', 'aż', 'bardziej', 'bardzo', 'bez', 'bo', 'bowiem', 'by', 'byli', 'być', 'był', 'była', 'było', 'były', 'będzie',
            'będą', 'cali', 'cała', 'cały', 'co', 'cokolwiek', 'coś', 'czasami', 'czasem', 'czemu', 'czy', 'czyli', 'dla', 'dlaczego', 'dlatego', 'do', 'gdy', 'gdyż', 'gdzie',
            'gdziekolwiek', 'gdzieś', 'go', 'i', 'ich', 'ile', 'im', 'inna', 'inne', 'inny', 'innych', 'iż', 'ja', 'jak', 'jakaś', 'jakichś', 'jakie', 'jakiś', 'jakiż',
            'jakkolwiek', 'jako', 'jakoś', 'jednak', 'jednakże', 'jego', 'jej', 'jest', 'jeszcze', 'jeśli', 'jeżeli', 'już', 'ją', 'kiedy', 'kilka', 'kimś', 'kto',
            'ktokolwiek', 'ktoś', 'która', 'które', 'którego', 'której', 'który', 'których', 'którym', 'którzy', 'lat', 'lecz', 'lub', 'ma', 'mają', 'mi', 'mimo', 'między',
            'mnie', 'mogą', 'moim', 'może', 'możliwe', 'można', 'mu', 'musi', 'my', 'na', 'nad', 'nam', 'nas', 'naszego', 'naszych', 'natomiast', 'nawet', 'nic', 'nich',
            'nie', 'nigdy', 'nim', 'niż', 'no', 'o', 'obok', 'od', 'około', 'on', 'ona', 'one', 'oni', 'ono', 'oraz', 'pan', 'pana', 'pani', 'po', 'pod', 'podczas', 'pomimo',
            'ponad', 'ponieważ', 'powinien', 'powinna', 'powinni', 'powinno', 'poza', 'prawie', 'przecież', 'przed', 'przede', 'przez', 'przy', 'roku', 'również', 'się',
            'sobie', 'sobą', 'sposób', 'swoje', 'są', 'ta', 'tak', 'taka', 'taki', 'takie', 'także', 'tam', 'tamto', 'te', 'tego', 'tej', 'ten', 'teraz', 'też', 'to', 'tobie',
            'toteż', 'trzeba', 'tu', 'twoim', 'twoja', 'twoje', 'twym', 'twój', 'ty', 'tych', 'tylko', 'tym', 'u', 'w', 'we', 'według', 'wiele', 'wielu', 'więc', 'więcej',
            'wszyscy', 'wszystkich', 'wszystkie', 'wszystkim', 'wszystko', 'wy', 'właśnie', 'z', 'za', 'zapewne', 'zawsze', 'ze', 'znowu', 'znów', 'został', 'żadna', 'żadne',
            'żadnych', 'że', 'żeby',

            'a', 'acerca', 'agora', 'ainda', 'alguém', 'algum', 'alguma', 'algumas', 'alguns', 'ali', 'ambos', 'ampla', 'amplas', 'amplo', 'amplos', 'ante', 'antes', 'apontar',
            'ao', 'aos', 'após', 'aquela', 'aquelas', 'aquele', 'aqueles', 'aqui', 'aquilo', 'as', 'até', 'atrás', 'através', 'bem', 'bom', 'cada', 'caminho', 'cima', 'coisa',
            'coisas', 'com', 'como', 'conhecido', 'contra', 'contudo', 'corrente', 'da', 'daquela', 'daquelas', 'daquele', 'daqueles', 'das', 'de', 'debaixo', 'dela', 'delas',
            'dele', 'deles', 'dentro', 'depois', 'desde', 'desligado', 'dessa', 'dessas', 'desse', 'desses', 'desta', 'destas', 'deste', 'deste', 'destes', 'deve', 'devem',
            'devendo', 'dever', 'dever', 'deverão', 'deveria', 'deveriam', 'devia', 'deviam', 'direita', 'disse', 'disso', 'disto', 'dito', 'diz', 'dizem', 'do', 'dois', 'dos',
            'e', 'é', 'ela', 'elas', 'ele', 'eles', 'em', 'enquanto', 'então', 'entre', 'era', 'essa', 'essas', 'esse', 'esses', 'esta', 'está', 'estamos', 'estão', 'estar',
            'estará', 'estas', 'estava', 'estavam', 'estávamos', 'este', 'estes', 'estou', 'eu', 'fará', 'faz', 'fazendo', 'fazer', 'fazia', 'feita', 'feitas', 'feito',
            'feitos', 'fez', 'fim', 'foi', 'for', 'fora', 'foram', 'fosse', 'fossem', 'grande', 'grandes', 'há', 'hora', 'horas', 'iniciar', 'início', 'ir', 'irá', 'isso',
            'isto', 'já', 'ligado', 'maioria', 'maiorias', 'mais', 'mas', 'me', 'mesma', 'mesmas', 'mesmo', 'mesmos', 'meu', 'meus', 'minha', 'minhas', 'muita', 'muitas',
            'muito', 'muitos', 'na', 'não', 'nas', 'nem', 'nenhum', 'nessa', 'nessas', 'nesta', 'nestas', 'ninguém', 'no', 'nome', 'nos', 'nós', 'nossa', 'nossas', 'nosso',
            'nossos', 'novo', 'num', 'numa', 'nunca', 'o', 'onde', 'os', 'ou', 'outra', 'outras', 'outro', 'outros', 'para', 'parte', 'pegar', 'pela', 'pelas', 'pelo',
            'pelos', 'pequena', 'pequenas', 'pequeno', 'pequenos', 'perante', 'pessoa', 'pessoas', 'pode', 'podendo', 'poder', 'poderá', 'poderia', 'poderiam', 'podia',
            'podiam', 'pois', 'por', 'porém', 'porque', 'posso', 'pouca', 'poucas', 'pouco', 'poucos', 'povo', 'primeiro', 'primeiros', 'própria', 'próprio', 'próprios',
            'quais', 'qual', 'qualquer', 'quando', 'quanto', 'quantos', 'que', 'quê', 'quem', 'saber', 'são', 'se', 'seja', 'sejam', 'sem', 'sempre', 'sendo', 'ser', 'será',
            'serão', 'seu', 'seus', 'si', 'sido', 'só', 'sob', 'sobre', 'somente', 'sua', 'suas', 'tal', 'talvez', 'também', 'tampouco', 'te', 'tem', 'têm', 'tempo', 'tendo',
            'tenha', 'tenho', 'tentaram', 'tentarem', 'ter', 'teu', 'teus', 'teve', 'ti', 'tido', 'tinha', 'tinham', 'tipo', 'tive', 'toda', 'todas', 'todavia', 'todo',
            'todos', 'tu', 'tua', 'tuas', 'tudo', 'última', 'últimas', 'último', 'últimos', 'um', 'uma', 'umas', 'uns', 'usa', 'usar', 'veja', 'vendo', 'ver', 'verdade',
            'verdadeiro', 'vez', 'vindo', 'vir', 'você'

        ];
        // phpcs:enable Generic.Files.LineLength
    }
}
