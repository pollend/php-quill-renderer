<?php

namespace DBlackborough\Quill\Renderer;

use DBlackborough\Quill\Renderer;

/**
 * Quill renderer, converts quill delta inserts into HTML
 *
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough
 * @license https://github.com/deanblackborough/php-quill-renderer/blob/master/LICENSE
 */
class Html extends Renderer
{
    /**
     * The generated HTML, string generated by the render method from the content array
     *
     * @var string
     */
    protected $html;

    /**
     * Renderer constructor.
     *
     * @param array @options Options data array, if empty default options are used
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
    }

    /**
     * Default options for HTML renderer, all options can be overridden, either by setting options in the
     * constructor or by using the setOption and setAttributeOption methods
     *
     * @return array
     */
    protected function defaultOptions()
    {
        return array(
            'attributes' => array(
                'bold' => array(
                    'tag' => 'strong'
                ),
                'italic' => array(
                    'tag' => 'em'
                ),
                'underline' => array(
                    'tag' => 'u'
                ),
                'strike' => array(
                    'tag' => 's'
                ),
                'link' => array(
                    'tag' => 'a',
                    'attributes' => array(
                        'href' => null
                    )
                )
            ),
            'block' => 'p',
            'newline' => 'br'
        );
    }

    /**
     * Get the tag(s) and attributes/values that have been defined for the quill attribute.
     *
     * @param string $attribute
     * @param string $value
     *
     * @return array|false
     */
    protected function getTagAndAttributes($attribute, $value)
    {
        switch ($attribute)
        {
            case 'bold':
            case 'italic':
            case 'underline':
            case 'strike':
                return $this->options['attributes'][$attribute];
                break;

            case 'link':
                $result = $this->options['attributes'][$attribute];
                $result['attributes']['href'] = $value;
                return $result;
                break;

            default:
                // Do nothing, valid already set to false
                return false;
                break;
        }
    }

    /**
     * Convert new lines into blocks and newlines
     *
     * @param string $subject
     * @param integer $i Content array index
     * @return array Two indexes, subject and tags
     */
    protected function convertNewlines($subject, $i)
    {
        $tags = array();

        if (preg_match("/[\n]{2,} */", $subject) === true) {
            $tags[] = array(
                'open' => null,
                'close' => '</' . $this->options['block'] . '>'
            );
            $tags[] = array(
                'open' => '<' . $this->options['block'] . '>',
                'close' => null,
            );

        }
        $subject = preg_replace("/[\n]{2,} */", '</' . $this->options['block'] . '><' . $this->options['block'] . '>', $subject);
        $subject = preg_replace("/[\n]{1}/", "<" . $this->options['newline'] . " />\n", $subject);

        return array(
            'tags' => $tags,
            'subject' => $subject
        );
    }

    /**
     * Check to see if the last content item is a block element, if it isn't add the default block element
     * defined by the block option
     */
    protected function lastItemBlockElement()
    {
        $last_item = count($this->content) - 1;
        $assigned_tags = $this->content[$last_item]['tags'];
        $block = false;

        if (count($assigned_tags) > 0) {
            foreach ($assigned_tags as $assigned_tag) {
                // Block element check
            }
        }

        if ($block === false) {
            $this->content[$last_item]['tags'] = array();
            foreach ($assigned_tags as $assigned_tag) {
                $this->content[$last_item]['tags'][] = $assigned_tag;
            }
            $this->content[$last_item]['tags'][] = array(
                'open' => null,
                'close' => '</' . $this->options['block'] . '>',
            );
        }
    }

    /**
     * Check to see if the first content item is a block element, if it isn't add the default block element
     * defined by the block option
     */
    protected function firstItemBlockElement()
    {
        $assigned_tags = $this->content[0]['tags'];
        $block = false;

        if (count($assigned_tags) > 0) {
            foreach ($assigned_tags as $assigned_tag) {
                // Block element check
            }
        }

        if ($block === false) {
            $this->content[0]['tags'][] = array(
                'open' => '<' . $this->options['block'] . '>',
                'close' => null
            );
            foreach ($assigned_tags as $assigned_tag) {
                $this->content[0]['tags'][] = $assigned_tag;
            }
        }
    }

    /**
     * Loop through the deltas and generate the contents array
     *
     * @return string
     */
    protected function parseDeltas()
    {
        if ($this->json_valid === true && array_key_exists('ops', $this->deltas) === true) {

            $inserts = count($this->deltas['ops']);

            $i = 0;

            foreach ($this->deltas['ops'] as $k => $insert) {

                $this->content[$i] = array(
                    'content' => null,
                    'tags' => array()
                );

                $tags = array();
                $has_tags = false;

                if (array_key_exists('attributes', $insert) === true && is_array($insert['attributes']) === true) {
                    foreach ($insert['attributes'] as $attribute => $value) {
                        if ($this->isAttributeValid($attribute, $value) === true) {
                            $tag = $this->getTagAndAttributes($attribute, $value);
                            if ($tag !== false) {
                                $tags[] = $tag;
                            }
                        }
                    }
                }

                if (count($tags) > 0) {
                    $has_tags = true; // Set bool so we don't need to check array size again
                }

                if ($has_tags === true) {
                    foreach ($tags as $tag) {
                        $open = '<' . $tag['tag'];
                        if (array_key_exists('attributes', $tag) === true) {
                            $open .= ' ';
                            foreach ($tag['attributes'] as $attribute => $value) {
                                $open .= $attribute . '="' . $value . '"';
                            }
                        }
                        $open .= '>';

                        $this->content[$i]['tags'][] = array(
                            'open' => $open,
                            'close' => '</' . $tag['tag'] . '>',
                        );
                    }
                }

                if (array_key_exists('insert', $insert) === true && strlen(trim($insert['insert'])) > 0) {
                    $content = $this->convertNewlines($insert['insert'], $i);
                    if (count($content['tags']) > 0) {
                        foreach($content['tags'] as $tag) {
                            $this->content[$i]['tags'][] = $tag;
                        }
                    }
                    $this->content[$i]['content'] = $content['subject'];
                }

                if ($k === ($inserts-1)) {
                    $this->content[$i]['content'] = rtrim($this->content[$i]['content'], '<' . $this->options['newline'] . " />\n");
                }

                $i++;
            }

            if (count($this->content) > 0) {
                $this->firstItemBlockElement();
                $this->LastItemBlockElement();
            }
        }

        $this->content_valid = true;
    }

    /**
     * Generate the final HTML from the contents array
     *
     * @return string
     */
    public function render()
    {
        $this->parseDeltas();

        if ($this->content_valid === true) {
            foreach ($this->content as $content) {
                foreach ($content['tags'] as $tag) {
                    if (array_key_exists('open', $tag) === true && $tag['open'] !== null) {
                        $this->html .= $tag['open'];
                    }
                }

                $this->html .= $content['content'];

                foreach (array_reverse($content['tags']) as $tag) {
                    if (array_key_exists('close', $tag) === true && $tag['close'] !== null) {
                        $this->html .= $tag['close'];
                    }
                }
            }
        }

        return $this->html;
    }
}