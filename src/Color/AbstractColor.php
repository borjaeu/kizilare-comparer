<?php
namespace Kizilare\Changes\Color;

abstract class AbstractColor
{
    const CATEGORY_DELETE = 'delete';
    const CATEGORY_INSERT = 'insert';

    /**
     * @param string $text
     * @param string $category
     * @return string
     */
    final public function colorize($text, $category)
    {
        switch ($category) {
            case self::CATEGORY_DELETE:
                $text = $this->getDeletedText($text);
                break;
            case self::CATEGORY_INSERT:
                $text = $this->getInsertedText($text);
                break;
            default:
                throw new \UnderflowException("Invalid category '$category'");
        }

        return $text;
    }

    /**
     * @param string $text
     * @return string
     */
    abstract protected function getDeletedText($text);

    /**
     * @param string $text
     * @return string
     */
    abstract protected function getInsertedText($text);
}
