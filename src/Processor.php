<?php
namespace Kizilare\Changes;

use Kizilare\Changes\Color\AbstractColor;

/**
 * Detect changes between strings.
 */
class Processor
{
    /**
     * @var AbstractColor
     */
    private $color;

    /**
     * Processor constructor.
     * @param AbstractColor $color
     */
    public function __construct(AbstractColor $color)
    {
        $this->color = $color;
    }

    /**
     * Displays a single change for text.
     *
     * @param string $old Previous value.
     * @param string $new New value.
    * @return string
     */
    public function showChange($old, $new)
    {
        if (empty($old)) {
            $change = "Set to '$new'";
        } elseif (empty($new))  {
            $change = "'$old' unset";
        } elseif (is_array($old) && is_array($new)) {
            $merge = $this->diff($old, $new);
            $change = $this->htmlDiff( $merge, ', ', 0, false );
        } else {
            $merge = $this->diff(explode(' ', $old), explode(' ', $new));
            $change = $this->htmlDiff($merge);
        }

        return $change;
    }

    /**
     * Gets an html with the differences between to text blocks.
     *
     * @param array     $merge      Array with the list of merged values, and diffs.
     * @param string    $sep        String used to separate words.
     * @param integer   $window     Number of words to keeps around changes.
     * @param boolean   $charLevel Specify changes at char level, not just words.
     * @return string
     */
    private function htmlDiff($merge, $sep = ' ', $window = 50, $charLevel = true)
    {
        $buffer = [];
        $postBuffer = 0;
        $result = [];
        foreach ($merge as $chunk) {
            if (is_array($chunk)) {
                $chunk['del'] = implode($sep, $chunk['del']);
                $chunk['ins'] = implode($sep, $chunk['ins']);
                if ($window > 0 && count($buffer) > 0) {
                    if (count($buffer) > $window) {
                        $result[] = '[...]';
                    }
                    $part = array_slice($buffer, - $window);
                    $result = array_merge($result, $part);
                    $buffer = [];
                }
                $result[] = implode('', $this->checkArray($chunk, $charLevel));
                $postBuffer = $window;
            } elseif ($postBuffer > 0) {
                $result[] = $chunk;
                $postBuffer--;
            } else {
                $buffer[] = $chunk;
            }
        }

        return implode($sep, $result);
    }

    /**
     * Detect differences between arrays.
     *
     * @param   array $old  Previous information.
     * @param   array $new  New information.
     * @return  array Merged array with the differences shown.
     */
    protected function diff($old, $new)
    {
        $maxLength = 0;
        $oldMax = 0;
        $newMax = 0;
        $matrix = [];

        foreach ($old as $oldIndex => $oldValue) {
            // Get the max length for equals array of values.
            $newKeys = array_keys($new, $oldValue);
            foreach ($newKeys as $newIndex) {
                $matrix[$oldIndex][$newIndex] = isset($matrix[($oldIndex - 1)][($newIndex - 1)] ) ? ($matrix[($oldIndex - 1)][($newIndex - 1)] + 1) : 1;
                if ($matrix[$oldIndex][$newIndex] > $maxLength) {
                    $maxLength = $matrix[$oldIndex][$newIndex];
                    $oldMax = ($oldIndex + 1 - $maxLength);
                    $newMax = ($oldIndex + 1 - $maxLength);
                }
            }
        }

        // If the $maxLength is empty, both array are completely different.
        if ($maxLength == 0 ) {
            if (count($old) || count($new)) {
                return [
                    ['del' => $old, 'ins' => $new]
                ];
            } else {
                return [];
            }
        }

        // Iterate over the chunks of equals arrays.
        return array_merge(
            $this->diff(array_slice($old, 0, $oldMax), array_slice($new, 0, $newMax)),
            array_slice($new, $newMax, $maxLength),
            $this->diff(array_slice($old, ($oldMax + $maxLength)), array_slice($new, ($newMax + $maxLength)))
        );
    }

    /**
     * Compares and prepares diff response for a full array.
     *
     * @param array     $chunk      Array to check.
     * @param boolean   $charLevel  Specify changes at char level, not just words.
     * @return array
     */
    protected function checkArray(array $chunk, $charLevel)
    {
        // Store an array with the diffs. If is at char level store prefix, word and suffix separately.
        $entry = ['p' => '', 'w' => '', 's' => ''];

        // Expand the differences at char level.
        if (!empty( $chunk['del'] ) && !empty( $chunk['ins'] ) && $charLevel) {
            $this->checkChunk($chunk, $entry);
        }

        // Now the word value is set with the inserted and deleted data.
        if (!empty($chunk['del'])) {
            $entry['w'] .= $this->color->colorize($chunk['del'], AbstractColor::CATEGORY_DELETE);
        }
        if (!empty( $chunk['ins'])) {
            $entry['w'] .= $this->color->colorize($chunk['ins'], AbstractColor::CATEGORY_INSERT);
        }

        return $entry;
    }

    /**
     * Compares and prepares diff response for an array.
     *
     * @param array &$chunk Array to check.
     * @param array &$entry Result array.
     */
    protected function checkChunk(array &$chunk, &$entry)
    {
        // First is checked letter by letter from the left.
        $max = min( strlen( $chunk['del'] ), strlen( $chunk['ins'] ) );

        for ($i = 0; $i < $max; $i++) {
            // Same char, is moved to the preffix entry and trimed the ins and del values.
            if ( substr( $chunk['ins'], 0, 1 ) == substr( $chunk['del'], 0, 1 ) ) {
                $entry['p'] .= substr( $chunk['ins'], 0, 1 );
                $chunk['ins'] = substr( $chunk['ins'], 1 );
                $chunk['del'] = substr( $chunk['del'], 1 );
            } else {
                break;
            }
        }

        // Then is checked letter by letter from the right.
        $max = min(strlen($chunk['del']), strlen($chunk['ins']));
        for ($i = ( $max - 1 ); $i >= 0; $i--) {
            // Same char, is moved to the preffix entry and trimed the ins and del values.
            if (substr($chunk['ins'], -1, 1 ) == substr($chunk['del'], -1, 1)) {
                $entry['s'] = substr( $chunk['ins'], -1, 1 ) . $entry['s'];
                $chunk['ins'] = substr( $chunk['ins'], 0, -1 );
                $chunk['del'] = substr( $chunk['del'], 0, -1 );
            } else {
                break;
            }
        }
    }
}
