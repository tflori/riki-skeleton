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
        $this->content = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
    public function prepend(string $line, string $content): Content
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
    public function replace(string $line, string $content): Content
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
    public function append(string $line, string $content): Content
    {
        $this->content = preg_replace('~^(' . $line . ')$~sm', '\1' . PHP_EOL . $content, $this->content);
        return $this;
    }

    /**
     * Add method after method named $after
     *
     * @param $methodDeclaration
     * @param string $after
     * @return $this
     */
    public function addMethod(string $methodDeclaration, string $after = null): Content
    {
        if (!preg_match('~(class|interface|trait)[a-z \n\\\\]+\{~ism', $this->content)) {
            throw new \Exception('Not a class, trait or interface');
        }

        $pos = strrpos($this->content, '}');
        $regex = '~^    [a-z ]*?' . // a method has to start with indentation of 4 and may have keywords in front
                 'function ' . $after . // the function keyword plus the method name
                 '\s*\([^)]*\)' . // parameter definitions (note: no closing parenthesis inside strings allowed)
                 '\s*(:\s*[a-z\\\n]+)?' . // maybe with return type declaration
                 '\s*(\{.*?\n    \}$|;$)' . // with a body or a semicolon at a end of line
                 '~ism';

        if ($after && preg_match($regex, $this->content, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]) + 1;
        }

        $this->content = substr_replace($this->content, $methodDeclaration, $pos, 0);
        return $this;
    }
}
