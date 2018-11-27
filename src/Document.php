<?php declare(strict_types=1);

namespace DOMWrap;

use DOMWrap\Traits\{
    CommonTrait,
    TraversalTrait,
    ManipulationTrait
};

/**
 * Document Node
 *
 * @package DOMWrap
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3 Clause
 */
class Document extends \DOMDocument
{
    use CommonTrait;
    use TraversalTrait;
    use ManipulationTrait;

    public function __construct(string $version = '1.0', string $encoding = 'UTF-8') {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMText', 'DOMWrap\\Text');
        $this->registerNodeClass('DOMElement', 'DOMWrap\\Element');
        $this->registerNodeClass('DOMComment', 'DOMWrap\\Comment');
        $this->registerNodeClass('DOMDocumentType', 'DOMWrap\\DocumentType');
        $this->registerNodeClass('DOMProcessingInstruction', 'DOMWrap\\ProcessingInstruction');
    }

    /**
     * {@inheritdoc}
     */
    public function document(): ?\DOMDocument {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function collection(): NodeList {
        return $this->newNodeList([$this]);
    }

    /**
     * {@inheritdoc}
     */
    public function result(NodeList $nodeList) {
        if ($nodeList->count()) {
            return $nodeList->first();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function parent() {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function parents() {
        return $this->newNodeList();
    }

    /**
     * {@inheritdoc}
     */
    public function replaceWith($newNode): self {
        $this->replaceChild($newNode, $this);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function _clone() {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml(): string {
        return $this->getOuterHtml();
    }

    /**
     * {@inheritdoc}
     */
    public function setHtml($html): self {
        if (!is_string($html) || trim($html) == '') {
            return $this;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);

        $html = $this->convertToUtf8($html);
        $this->loadHTML($html);

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        return $this;
    }

    private function getCharset(string $html): ?string {
        $charset = null;

        if (preg_match('@<meta.*?charset=["]?([^"\s]+)@im', $html, $matches)) {
            $charset = strtoupper($matches[1]);
        }

        return $charset;
    }
        
    private function convertToUtf8(string $html): string {
        if (mb_detect_encoding($html, mb_detect_order(), true) === 'UTF-8') {
            return $html;
        }

        $charset = $this->getCharset($html);

        if ($charset !== null) {
            $html = preg_replace('@(charset=["]?)([^"\s]+)([^"]*["]?)@im', '$1UTF-8$3', $html);
            $mbHasCharset = in_array($charset, array_map('strtoupper', mb_list_encodings()));

            if ($mbHasCharset) {
                $html = mb_convert_encoding($html, 'UTF-8', $charset);

            // Fallback to iconv if available.
            } elseif (extension_loaded('iconv')) {
                $htmlIconv = iconv($charset, 'UTF-8', $html);

                if ($htmlIconv !== false) {
                    $html = $htmlIconv;
                } else {
                    $charset = null;
                }
            }
        }

        if ($charset === null) {
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }

        return $html;
    }
}
