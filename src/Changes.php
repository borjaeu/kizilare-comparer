<?php
namespace Kizilare;

/**
 * Detecte changes
 */
class Changes
{

	/**
	 * Displays a single change for text.
	 *
	 * @param string $old Previous value.
	 * @param string $new New value.
	 * @return string
	 */
	public function showChange( $old, $new )
	{
		if ( empty( $old ) )
		{
			$change = "Set to '$new'";
		}
		elseif ( empty( $new ) )
		{
			$change = "'$old' unset";
		}
		elseif ( is_array( $old ) && is_array( $new ) )
		{
			$merge = $this->diff( $old, $new );
			$change = $this->htmlDiff( $merge, ', ', 0, false );
		}
		else
		{
			$merge = $this->diff( explode( ' ', $old ), explode( ' ', $new ) );
			$change = $this->htmlDiff( $merge );
		}
		return $change;
	}

	/**
	 * Gets an html with the differences between to text blocks.
	 *
	 * @param array $merge Array with the list of merged values, and diffs.
	 * @param string $sep String used to separate words.
	 * @param integer $window Number of words to keeps around changes.
	 * @param boolean $char_level Specify changes at char level, not just words.
	 * @return string
	 */
	protected function htmlDiff( $merge, $sep = ' ', $window = 100, $char_level = true )
	{
		$buffer = array();
		$postbuffer = 0;
		$result = array();
		foreach ( $merge as $chunk )
		{
			if ( is_array( $chunk ) )
			{
				$chunk['del'] = implode( $sep, $chunk['del'] );
				$chunk['ins'] = implode( $sep, $chunk['ins'] );

				// Add the equals words to the result, as many as the windows is set.
				if ( $window > 0 && count( $buffer ) > 0 )
				{
					if ( count( $buffer ) > $window )
					{
						$result[] = '[...]';
					}
					$part = array_slice( $buffer, - $window );
					$result = array_merge( $result, $part );
					$buffer = array();
				}

				// All preffix, word and suffix join is added to the result.
				$result[] = implode( '', $this->checkArray( $chunk, $char_level ) );

				// We set the number of equal words to store after the diff.
				$postbuffer = $window;
			}
			elseif ( $postbuffer > 0 )
			{
				// Keep adding equal words to the result.
				$result[] = $chunk;
				$postbuffer--;
			}
			else
			{
				$buffer[] = $chunk;
			}
		}
		return implode( $sep, $result );
	}

		/**
	 * Detect differences between arrays.
	 *
	 * @param array $old Previous information.
	 * @param array $new New information.
	 * @return array Merged array with the diffences shown.
	 */
	protected function diff( $old, $new )
	{
		$maxlen = 0;
		$matrix = array();
		foreach ( $old as $oindex => $ovalue )
		{
			// Get the max length for equals array of values.
			$nkeys = array_keys( $new, $ovalue );
			foreach ( $nkeys as $nindex )
			{
				$matrix[$oindex][$nindex] = isset( $matrix[( $oindex - 1 )][( $nindex - 1 )] ) ? ( $matrix[( $oindex - 1 )][( $nindex - 1 )] + 1 ) : 1;
				if ( $matrix[$oindex][$nindex] > $maxlen )
				{
					$maxlen = $matrix[$oindex][$nindex];
					$omax = ( $oindex + 1 - $maxlen );
					$nmax = ( $nindex + 1 - $maxlen );
				}
			}
		}

		// If the maxlen is empty, both array are diffent.
		if ( $maxlen == 0 )
		{
			if ( count( $old ) || count( $new ) )
			{
				return array(
					array(
						'del' => $old,
						'ins' => $new
					)
				);
			}
			else
			{
				return array();
			}
		}
		// Iterate over the chunks of equals arrays.
		return array_merge(
			$this->diff( array_slice( $old, 0, $omax ), array_slice( $new, 0, $nmax ) ),
			array_slice( $new, $nmax, $maxlen ),
			$this->diff( array_slice( $old, ( $omax + $maxlen ) ), array_slice( $new, ( $nmax + $maxlen ) ) )
		);
	}

		/**
	 * Compares and prepares diff response for a full array.
	 *
	 * @param array $chunk Array to check.
	 * @param boolean $char_level Specify changes at char level, not just words.
	 * @return array
	 */
	protected function checkArray( array $chunk, $char_level )
	{
		// Store an array with the diffs. If is at char level store preffix, word and suffix separately.
		$entry = array(
			'p' => '',
			'w' => '',
			's' => ''
		);

		// Expand the differences at char level.
		if ( !empty( $chunk['del'] ) && !empty( $chunk['ins'] ) && $char_level )
		{
			$this->checkChunk( $chunk, $entry );
		}

		// Now the word value is set with the inserted and deleted data.
		if ( !empty( $chunk['del'] ) )
		{
			$entry['w'] .= "<del>{$chunk['del']}</del>";
		}
		if ( !empty( $chunk['ins'] ) )
		{
			$entry['w'] .= "<ins>{$chunk['ins']}</ins>";
		}

		return $entry;
	}

		/**
	 * Compares and prepares diff response for an array.
	 *
	 * @param array &$chunk Array to check.
	 * @param array &$entry Result array.
	 */
	protected function checkChunk( array &$chunk, &$entry )
	{
		// First is checked letter by letter from the left.
		$max = min( strlen( $chunk['del'] ), strlen( $chunk['ins'] ) );
		for ( $i = 0; $i < $max; $i++ )
		{
			// Same char, is moved to the preffix entry and trimed the ins and del values.
			if ( substr( $chunk['ins'], 0, 1 ) == substr( $chunk['del'], 0, 1 ) )
			{
				$entry['p'] .= substr( $chunk['ins'], 0, 1 );
				$chunk['ins'] = substr( $chunk['ins'], 1 );
				$chunk['del'] = substr( $chunk['del'], 1 );
			}
			else
			{
				break;
			}
		}

		// Then is checked letter by letter from the right.
		$max = min( strlen( $chunk['del'] ), strlen( $chunk['ins'] ) );
		for ( $i = ( $max - 1 ); $i >= 0; $i-- )
		{
			// Same char, is moved to the preffix entry and trimed the ins and del values.
			if ( substr( $chunk['ins'], -1, 1 ) == substr( $chunk['del'], -1, 1 ) )
			{
				$entry['s'] = substr( $chunk['ins'], -1, 1 ) . $entry['s'];
				$chunk['ins'] = substr( $chunk['ins'], 0, -1 );
				$chunk['del'] = substr( $chunk['del'], 0, -1 );
			}
			else
			{
				break;
			}
		}
	}
}