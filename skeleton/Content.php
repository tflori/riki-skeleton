<?php

namespace Skeleton;

use Ckr\Util\ArrayMerger;

class Content
{
    /** @var string */
    protected $content;

    /**
     * Content constructor.
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return $this->content;
    }

    /**
     * Set the content to json $content
     *
     * @param array $content
     * @return $this
     */
    public function json(array $content): Content
    {
        $this->content = json_encode($content, JSON_PRETTY_PRINT);
        return $this;
    }

    /**
     * Merge the current json content with $additional
     *
     * It's a recursive merge and if the current content can not be decoded it is getting replaced.
     *
     * @param array $additional
     * @return $this
     */
    public function mergeJson(array $additional): Content
    {
        $this->content = json_encode(ArrayMerger::doMerge(
            json_decode($this->content, true) ?? [],
            $additional
        ), JSON_PRETTY_PRINT);
        return $this;
    }

    /**
     * Prepend $line with $content
     *
     * $line is a regular expression so keep in mind for proper masking of special characters.
     *
     * You can also refer to sub patterns keep in mind that the whole line is a subpattern added after $content.
     *
     * @param $line
     * @param $content
     * @return $this
     */
    public function prepend($line, $content)
    {
        $this->content = preg_replace('~^(' . $line . ')$~sm', $content . PHP_EOL . '\1', $this->content);
        return $this;
    }

    /**
     * Prepend $line with $content
     *
     * $line is a regular expression so keep in mind for proper masking of special characters.
     *
     * You can also refer to sub patterns keep in mind that the whole line is a subpattern.
     *
     * @param $line
     * @param $content
     * @return $this
     */
    public function replace($line, $content)
    {
        $this->content = preg_replace('~^(' . $line . ')$~sm', $content, $this->content);
        return $this;
    }

    /**
     * Prepend $line with $content
     *
     * $line is a regular expression so keep in mind for proper masking of special characters.
     *
     * You can also refer to sub patterns keep in mind that the whole line is a subpattern.
     *
     * @param $line
     * @param $content
     * @return $this
     */
    public function append($line, $content)
    {
        $this->content = preg_replace('~^(' . $line . ')$~sm', '\1' . PHP_EOL . $content, $this->content);
        return $this;
    }
}
