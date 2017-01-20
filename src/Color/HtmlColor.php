<?php
namespace Kizilare\Changes\Color;

class HtmlColor extends AbstractColor
{
    /**
     * {@inheritdoc}
     */
    protected function getDeletedText($text)
    {
        return "<del>{$text}</del>";
    }

    /**
     * {@inheritdoc}
     */
    protected function getInsertedText($text)
    {
        return "<ins>{$text}</ins>";
    }
}
