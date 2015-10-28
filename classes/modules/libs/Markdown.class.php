<?php
#
# Based On Markdown  -  A text-to-HTML conversion tool for web writers
#
# PHP Markdown
# Copyright (c) 2004-2012 Michel Fortin  
# <http://michelf.com/projects/php-markdown/>
# MARKDOWN_VERSION 1.0.1 / Sun 8 Jan 2012
#
# Original Markdown
# Copyright (c) 2004-2006 John Gruber  
# <http://daringfireball.net/projects/markdown/>


// Change to ">" for HTML output
@define( 'MARKDOWN_EMPTY_ELEMENT_SUFFIX',  " />");

// Define the width of a tab for code blocks.
@define( 'MARKDOWN_TAB_WIDTH',     4 );

/**
 * Markdown Parser Class
 */
class MarkdownParser {

	// Regex to match balanced [brackets].
	// Needed to insert a maximum bracked depth while converting to PHP.
	public $nested_brackets_depth = 6;
	public $nested_brackets_re;
	
	public $nested_url_parenthesis_depth = 4;
	public $nested_url_parenthesis_re;

	// Table of hash values for escaped characters:
	public $escape_chars = '\`*_{}[]()>#+-.!';
	public $escape_chars_re;

	// Change to ">" for HTML output.
	public $empty_element_suffix = MARKDOWN_EMPTY_ELEMENT_SUFFIX;
	public $tab_width = MARKDOWN_TAB_WIDTH;
	
	// Change to `true` to disallow markup or entities.
	public $no_markup = false;
	public $no_entities = false;
	
	// Predefined urls and titles for reference links and images.
	public $predef_urls = array();
	public $predef_titles = array();


	public $replace = array(
       '/' => 'em',
       '+' => 'small', 
       '-' => 'strike',
       '*' => 'strong',
       '^' => 'sup',
       'v' => 'sub',
       '_' => 'u'
    );

	function Markdown_Parser()
    {
	#
	# Constructor function. Initialize appropriate member variables.
	#
		$this->_initDetab();
	
		$this->nested_brackets_re = 
			str_repeat('(?>[^\[\]]+|\[', $this->nested_brackets_depth).
			str_repeat('\])*', $this->nested_brackets_depth);
	
		$this->nested_url_parenthesis_re = 
			str_repeat('(?>[^()\s]+|\(', $this->nested_url_parenthesis_depth).
			str_repeat('(?>\)))*', $this->nested_url_parenthesis_depth);
		
		$this->escape_chars_re = '['.preg_quote($this->escape_chars).']';
		
		// Sort document, block, and span gamut in ascendent priority order.
		asort($this->document_gamut);
		asort($this->block_gamut);
		asort($this->span_gamut);
	}


	// Internal hashes used during transformation.
	public $urls = array();
	public $titles = array();
	public $html_hashes = array();
	
	// Status flag to avoid invalid nesting.
	public $in_anchor = false;

    /**
     * Called before the transformation process starts to setup parser
     * states.
     */
    function setup()
    {
		// Clear global hashes.
		$this->urls = $this->predef_urls;
		$this->titles = $this->predef_titles;
		$this->html_hashes = array();
		
		$in_anchor = false;
	}

    /**
     * Called after the transformation process to clear any variable
     * which may be taking up memory unnecessarly.
     */
    function teardown()
    {
		$this->urls = array();
		$this->titles = array();
		$this->html_hashes = array();
	}

    /**
     * Main function. Performs some preprocessing on the input text
     * and pass it through the document gamut.
     * @param $text
     * @return string
     */
    function transform($text)
    {
		$this->setup();
	
		// Remove UTF-8 BOM and marker character in input, if present.
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		// Standardize line endings:
		//   DOS to Unix and Mac to Unix
		$text = preg_replace('{\r\n?}', "\n", $text);
		
		// Make sure $text ends with a couple of newlines:
		$text .= "\n\n";

		// Convert all tabs to spaces.
		$text = $this->detab($text);

		// Turn block-level HTML blocks into hash entries
		$text = $this->hashHTMLBlocks($text);

		// Strip any lines consisting only of spaces and tabs.
		// This makes subsequent regexen easier to write, because we can
		// match consecutive blank lines with /\n+/ instead of something
		// contorted like /[ ]*\n+/ .
		$text = preg_replace('/^[ ]+$/m', '', $text);

		// Run document gamut methods.
		foreach ($this->document_gamut as $method => $priority) {
			$text = $this->$method($text);
		}
		
		$this->teardown();

		$text = str_replace("<p><cut>", "<cut><p>", $text);
		$text = str_replace("</cut></p>", "</p></cut>", $text);

		return $text . "\n";
	}
	
	public $document_gamut = array(
		# Strip link definitions, store in hashes.
		
		"runBasicBlockGamut"   => 30,
		);


    /**
     * Hashify HTML blocks:
     * @param $text
     * @return mixed
     */
    function hashHTMLBlocks($text)
    {
		if ($this->no_markup)  return $text;

		$less_than_tab = $this->tab_width - 1;

		# Hashify HTML blocks:
		# We only want to do this for block-level HTML tags, such as headers,
		# lists, and tables. That's because we still want to wrap <p>s around
		# "paragraphs" that are wrapped in non-block-level tags, such as anchors,
		# phrase emphasis, and spans. The list of tags we're looking for is
		# hard-coded:
		#
		# *  List "a" is made of tags which can be both inline or block-level.
		#    These will be treated block-level when the start tag is alone on 
		#    its line, otherwise they're not matched here and will be taken as 
		#    inline later.
		# *  List "b" is made of tags which are always block-level;
		#
		$block_tags_a_re = 'ins|del';
		$block_tags_b_re = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|'.
						   'script|noscript|form|fieldset|iframe|math';

		# Regular expression for the content of a block tag.
		$nested_tags_level = 4;
		$attr = '
			(?>				# optional tag attributes
			  \s			# starts with whitespace
			  (?>
				[^>"/]+		# text outside quotes
			  |
				/+(?!>)		# slash not followed by ">"
			  |
				"[^"]*"		# text inside double quotes (tolerate ">")
			  |
				\'[^\']*\'	# text inside single quotes (tolerate ">")
			  )*
			)?	
			';
		$content =
			str_repeat('
				(?>
				  [^<]+			# content without tag
				|
				  <\2			# nested opening tag
					'.$attr.'	# attributes
					(?>
					  />
					|
					  >', $nested_tags_level).	// end of opening tag
					  '.*?'.					// last level nested tag content
			str_repeat('
					  </\2\s*>	# closing nested tag
					)
				  |				
					<(?!/\2\s*>	# other tags with a different name
				  )
				)*',
				$nested_tags_level);
		$content2 = str_replace('\2', '\3', $content);

		# First, look for nested blocks, e.g.:
		# 	<div>
		# 		<div>
		# 		tags for inner block must be indented.
		# 		</div>
		# 	</div>
		#
		# The outermost tags must start at the left margin for this to match, and
		# the inner nested divs must be indented.
		# We need to do this before the next, more liberal match, because the next
		# match will start at the first `<div>` and stop at the first `</div>`.
		$text = preg_replace_callback('{(?>
			(?>
				(?<=\n\n)		# Starting after a blank line
				|				# or
				\A\n?			# the beginning of the doc
			)
			(						# save in $1

			  # Match from `\n<tag>` to `</tag>\n`, handling nested tags 
			  # in between.
					
						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_b_re.')# start tag = $2
						'.$attr.'>			# attributes followed by > and \n
						'.$content.'		# content, support nesting
						</\2>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document

			| # Special version for tags of group a.

						[ ]{0,'.$less_than_tab.'}
						<('.$block_tags_a_re.')# start tag = $3
						'.$attr.'>[ ]*\n	# attributes followed by >
						'.$content2.'		# content, support nesting
						</\3>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
					
			| # Special case just for <hr />. It was easier to make a special 
			  # case than to make the other regex more complicated.
			
						[ ]{0,'.$less_than_tab.'}
						<(hr)				# start tag = $2
						'.$attr.'			# attributes
						/?>					# the matching end tag
						[ ]*
						(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # Special case for standalone HTML comments:
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<!-- .*? -->
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			| # PHP and ASP-style processor instructions (<? and <%)
			
					[ ]{0,'.$less_than_tab.'}
					(?s:
						<([?%])			# $2
						.*?
						\2>
					)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
					
			)
			)}Sxmi',
			array(&$this, '_hashHTMLBlocks_callback'),
			$text);

		return $text;
	}

    /**
     * @param $matches
     * @return string
     */
    function _hashHTMLBlocks_callback($matches)
    {
		$text = $matches[1];
		$key  = $this->hashBlock($text);
		return "\n\n$key\n\n";
	}


    /**
     * Called whenever a tag must be hashed when a function insert an atomic
     * element in the text stream. Passing $text to through this function gives
     * a unique text-token which will be reverted back when calling unhash.
     *
     * The $boundary argument specify what character should be used to surround
     * the token. By convension, "B" is used for block elements that needs not
     * to be wrapped into paragraph tags at the end, ":" is used for elements
     * that are word separators and "X" is used in the general case
     *
     * @param $text
     * @param string $boundary
     * @return string
     */
    function hashPart($text, $boundary = 'X')
    {
		// Swap back any tag hash found in $text so we do not have to `unhash`
		// multiple times at the end.
		$text = $this->unhash($text);
		
		// Then hash the block.
		static $i = 0;
		$key = "$boundary\x1A" . ++$i . $boundary;
		$this->html_hashes[$key] = $text;
		return $key; // String that will replace the tag.
	}


    /**
     * Shortcut function for hashPart with block-level boundaries.
     * @param $text
     * @return string
     */
    function hashBlock($text)
    {
		return $this->hashPart($text, 'B');
	}


	public $block_gamut = array(
	/*
	# These are all the transformations that form block-level
	# tags like paragraphs, headers, and list items.
	*/
		"doHeaders"         => 10,
		
		"doLinks"           => 30,
		"doLists"           => 40,
		"doBlockQuotes"     => 60,
		);

    /**
     * Run block gamut tranformations.
     * @param $text
     * @return string
     */
    function runBlockGamut($text)
    {
		# We need to escape raw HTML in Markdown source before doing anything
		# else. This need to be done for each block, and not only at the 
		# begining in the Markdown function since hashed blocks can be part of
		# list items and could have been indented. Indented blocks would have 
		# been seen as a code block in a previous pass of hashHTMLBlocks.
		$text = $this->hashHTMLBlocks($text);
		
		return $this->runBasicBlockGamut($text);
	}

    /**
     * Run block gamut tranformations, without hashing HTML blocks. This is
     * useful when HTML blocks are known to be already hashed, like in the first
     * whole-document pass.
     *
     * @param $text
     * @return string
     */
    function runBasicBlockGamut($text)
    {
		foreach ($this->block_gamut as $method => $priority) {
			$text = $this->$method($text);
		}
		
		# Finally form paragraph and restore hashed blocks.
		$text = $this->formParagraphs($text);

		return $text;
	}


	public $span_gamut = array(
	#
	# These are all the transformations that occur *within* block-level
	# tags like paragraphs, headers, and list items.
	#
		# Process character escapes, code spans, and inline HTML
		# in one shot.
		"parseSpan"           => -30,
		
		"doLinks"    =>  40,
		"doItalicsAndBold"    =>  50,
		);

    /**
     * Run span gamut tranformations.
     * @param $text
     * @return mixed
     */
    function runSpanGamut($text)
    {
		foreach ($this->span_gamut as $method => $priority) {
			$text = $this->$method($text);
		}

		return $text;
	}

    /**
     * @param $text
     * @return mixed
     */
    function doLinks($text)
    {
		$text = preg_replace("/(\(\()([^ ]+)\s([^\)]+)(\)\))/i", '<a href="$2">$3</a>', $text);
		return $text;
	}

    /**
     * @param $text
     * @return mixed
     */
    function doHeaders($text)
    {
		# Setext-style headers:
		#	  Header 1
		#	  ========
		#  
		#	  Header 2
		#	  --------
		#
		$text = preg_replace_callback('{ ^(.+?)[ ]*\n(=+|-+)[ ]*\n+ }mx',
			array(&$this, '_doHeaders_callback_setext'), $text);

		# atx-style headers:
		#	# Header 1
		#	## Header 2
		#	## Header 2 with closing hashes ##
		#	...
		#	###### Header 6
		#
		$text = preg_replace_callback('{
				^(\#{1,3})	# $1 = string of #\'s
				[ ]*
				(.+?)		# $2 = Header text
				[ ]*
				\#*			# optional closing #\'s (not counted)
				\n+
			}xm',
			array(&$this, '_doHeaders_callback_atx'), $text);
		$text = preg_replace_callback('{
				^(\={1,3})	# $1 = string of #\'s
				[ ]*
				(.+?)		# $2 = Header text
				[ ]*
				\#*			# optional closing #\'s (not counted)
				\n+
			}xm',
			array(&$this, '_doHeaders_callback_atx'), $text);

		return $text;
	}

    /**
     * @param $matches
     * @return string
     */
    function _doHeaders_callback_setext($matches)
    {
		# Terrible hack to check we haven't found an empty list item.
		if ($matches[2] == '-' && preg_match('{^-(?: |$)}', $matches[1]))
			return $matches[0];
		
		$level = $matches[2]{0} == '=' ? 1 : 2;
		$block = "<h$level>".$this->runSpanGamut($matches[1])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}

    /**
     * @param $matches
     * @return string
     */
    function _doHeaders_callback_atx($matches)
    {
		$level = strlen($matches[1])+3;
		$block = "<h$level>".$this->runSpanGamut($matches[2])."</h$level>";
		return "\n" . $this->hashBlock($block) . "\n\n";
	}


    /**
     * Form HTML ordered (numbered) and unordered (bulleted) lists.
     * @param $text
     * @return mixed
     */
    function doLists($text)
    {
		$less_than_tab = $this->tab_width - 1;

		# Re-usable patterns to match list item bullets and number markers:
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";

		$markers_relist = array(
			$marker_ul_re => $marker_ol_re,
			$marker_ol_re => $marker_ul_re,
			);

		foreach ($markers_relist as $marker_re => $other_marker_re) {
			# Re-usable pattern to match any entirel ul or ol list:
			$whole_list_re = '
				(								# $1 = whole list
				  (								# $2
					([ ]{0,'.$less_than_tab.'})	# $3 = number of spaces
					('.$marker_re.')			# $4 = first list item marker
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						'.$marker_re.'[ ]+
					  )
					|
					  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						'.$other_marker_re.'[ ]+
					  )
				  )
				)
			'; // mx
			
			# We use a different prefix before nested lists than top-level lists.
			# See extended comment in _ProcessListItems().
		
			if ($this->list_level) {
				$text = preg_replace_callback('{
						^
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
			else {
				$text = preg_replace_callback('{
						(?:(?<=\n)\n|\A\n?) # Must eat the newline
						'.$whole_list_re.'
					}mx',
					array(&$this, '_doLists_callback'), $text);
			}
		}

		return $text;
	}

    /**
     * Re-usable patterns to match list item bullets and number markers:
     * @param $matches
     * @return string
     */
    function _doLists_callback($matches)
    {
		$marker_ul_re  = '[*+-]';
		$marker_ol_re  = '\d+[\.]';
		$marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";
		
		$list = $matches[1];
		$list_type = preg_match("/$marker_ul_re/", $matches[4]) ? "ul" : "ol";
		
		$marker_any_re = ( $list_type == "ul" ? $marker_ul_re : $marker_ol_re );
		
		$list .= "\n";
		$result = $this->processListItems($list, $marker_any_re);
		
		$result = $this->hashBlock("<$list_type>" . $result . "</$list_type>");
		return "\n". $result ."\n\n";
	}

	public $list_level = 0;

    /**
     * Process the contents of a single ordered or unordered list, splitting it
     * into individual list items.
     *
     * @param $list_str
     * @param $marker_any_re
     * @return mixed
     */
    function processListItems($list_str, $marker_any_re)
    {
		# The $this->list_level global keeps track of when we're inside a list.
		# Each time we enter a list, we increment it; when we leave a list,
		# we decrement. If it's zero, we're not in a list anymore.
		#
		# We do this because when we're not inside a list, we want to treat
		# something like this:
		#
		#		I recommend upgrading to version
		#		8. Oops, now this line is treated
		#		as a sub-list.
		#
		# As a single paragraph, despite the fact that the second line starts
		# with a digit-period-space sequence.
		#
		# Whereas when we're inside a list (or sub-list), that line will be
		# treated as the start of a sub-list. What a kludge, huh? This is
		# an aspect of Markdown's syntax that's hard to parse perfectly
		# without resorting to mind-reading. Perhaps the solution is to
		# change the syntax rules such that sub-lists must start with a
		# starting cardinal number; e.g. "1." or "a.".
		
		$this->list_level++;

		# trim trailing blank lines:
		$list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

		$list_str = preg_replace_callback('{
			(\n)?							# leading line = $1
			(^[ ]*)							# leading whitespace = $2
			('.$marker_any_re.'				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 ('.$marker_any_re.') (?:[ ]+|(?=\n))))
			}xm',
			array(&$this, '_processListItems_callback'), $list_str);

		$this->list_level--;
		return $list_str;
	}

    /**
     * @param $matches
     * @return string
     */
    function _processListItems_callback($matches)
    {
		$item = $matches[4];
		$leading_line =& $matches[1];
		$leading_space =& $matches[2];
		$marker_space = $matches[3];
		$tailing_blank_line =& $matches[5];

		if ($leading_line || $tailing_blank_line || 
			preg_match('/\n{2,}/', $item))
		{
			# Replace marker with the appropriate whitespace indentation
			$item = $leading_space . str_repeat(' ', strlen($marker_space)) . $item;
			$item = $this->runBlockGamut($this->outdent($item));
		}
		else {
			# Recursion for sub-lists:
			$item = $this->doLists($this->outdent($item));
			$item = preg_replace('/\n+$/', '', $item);
			$item = $this->runSpanGamut($item);
		}

		return "<li>" . $item . "</li>";
	}

    /**
     * @param $text
     * @return mixed
     */
    function doItalicsAndBold($text)
    {
		# delimiters
		foreach ($this->replace as $index => $value) { 
			$quotedValue = preg_quote($index, '#'); 
			$text = preg_replace_callback('#(?<!:)('.$quotedValue.'{2,})(?!\s)(.+?)(?<!\s)('.$quotedValue.'{2,})#s',array ($this, '_doItalicsAndBold_callback'), $text);
		}; 
		return $text;
	}

    /**
     * @param $found
     * @return string
     */
    function _doItalicsAndBold_callback($found)
	{

		#echo '<pre>';
		#print_r ($found);
		#echo '</pre>';
		if (strlen ($found[1]) > 2) {
		 $found[2] = substr ($found[1], 2) . $found[2];
		 $found[1] = substr ($found[1], 0, 2);
		}
		if (strlen ($found[3]) > 2) {
		 $found[2] = $found[2] . substr ($found[3], 0, -2);
		 $found[3] = substr ($found[1], -2);
		}
		#echo '<pre>';
		#print_r ($found);
		#echo '</pre>';
		return (
		 substr($found[1],2).'<'.$this->replace[$found[1][0]].'>'.$found[2].'</'.$this->replace[$found[1][0]].'>'.substr($found[3],2)
		);
	}

    /**
     * @param $text
     * @return mixed
     */
    function doBlockQuotes($text)
    {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*>[ ]?			# ">" at the start of a line
					.+\n					# rest of the first line
				  (.+\n)*					# subsequent consecutive lines
				  \n*						# blanks
				)+
			  )
			/xm',
			array(&$this, '_doBlockQuotes_callback'), $text);

		return $text;
	}

    /**
     * @param $matches
     * @return string
     */
    function _doBlockQuotes_callback($matches)
    {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);		# recurse

		$bq = preg_replace('/^/m', "", $bq);
		# These leading spaces cause problem with <pre> content, 
		# so we need to fix that:
		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', 
			array(&$this, '_doBlockQuotes_callback2'), $bq);
		// $bq = str_replace("\n\n", "\n", $bq);
		return "\n". $this->hashBlock("<blockquote>$bq</blockquote>")."\n\n";
	}

    /**
     * @param $matches
     * @return mixed
     */
    function _doBlockQuotes_callback2($matches)
    {
		$pre = $matches[1];
		$pre = preg_replace('/^  /m', '', $pre);
		return $pre;
	}


    /**
     * @param $text - string to process with html <p> tags
     * @return string
     */
    function formParagraphs($text)
    {
		# Strip leading and trailing lines:
		$text = preg_replace('/\A\n+|\n+\z/', '', $text);

		$grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

		#
		# Wrap <p> tags and unhashify HTML blocks
		#
		foreach ($grafs as $key => $value) {
			if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
				# Is a paragraph.
				$value = $this->runSpanGamut($value);
				$value = preg_replace('/^([ ]*)/', "<p>", $value);
				$value .= "</p>";
				$grafs[$key] = $this->unhash($value);
			}
			else {
				# Is a block.
				# Modify elements of @grafs in-place...
				$graf = $value;
				$block = $this->html_hashes[$graf];
				$graf = $block;
				$grafs[$key] = $graf;
			}
			$grafs[$key] = str_replace("\n","<br />",$grafs[$key]);
		}
		return implode("", $grafs);
	}


    /**
     * Encode text for a double-quoted HTML attribute. This function
     * is *not* suitable for attributes enclosed in single quotes.
     * @param $text
     * @return mixed
     */
    function encodeAttribute($text)
    {
		$text = $this->encodeAmpsAndAngles($text);
		$text = str_replace('"', '&quot;', $text);
		return $text;
	}


    /**
     * Take the string $str and parse it into tokens, hashing embeded HTML,
     * escaped characters and handling code spans.
     * @param $str
     * @return string
     */
    function parseSpan($str)
    {
		$output = '';
		
		$span_re = '{
				(
					\\\\'.$this->escape_chars_re.'
				|
					(?<![`\\\\])
					`+						# code span marker
			'.( $this->no_markup ? '' : '
				|
					<!--    .*?     -->		# comment
				|
					<\?.*?\?> | <%.*?%>		# processing instruction
				|
					<[/!$]?[-a-zA-Z0-9:_]+	# regular tags
					(?>
						\s
						(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*
					)?
					>
			').'
				)
				}xs';

		while (true) {
			#
			# Each loop iteration seach for either the next tag, the next 
			# openning code span marker, or the next escaped character. 
			# Each token is then passed to handleSpanToken.
			#
			$parts = preg_split($span_re, $str, 2, PREG_SPLIT_DELIM_CAPTURE);
			
			# Create token from text preceding tag.
			if ($parts[0] != "") {
				$output .= $parts[0];
			}
			
			# Check if we reach the end.
			if (isset($parts[1])) {
				$output .= $this->handleSpanToken($parts[1], $parts[2]);
				$str = $parts[2];
			}
			else {
				break;
			}
		}
		
		return $output;
	}


    /**
     * Handle $token provided by parseSpan by determining its nature and
     * returning the corresponding value that should replace it.
     * @param $token
     * @param $str
     * @return string
     */
    function handleSpanToken($token, &$str)
    {
		switch ($token{0}) {
			case "\\":
				if(isset($token{1})) {
					return $this->hashPart("&#". ord($token{1}). ";");
				} else {

				}
			case "`":
				# Search for end marker in remaining text.
				if (preg_match('/^(.*?[^`])'.preg_quote($token).'(?!`)(.*)$/sm', 
					$str, $matches))
				{
					$str = $matches[2];
					$codespan = $this->makeCodeSpan($matches[1]);
					return $this->hashPart($codespan);
				}
				return $token; // return as text since no ending marker found.
			default:
				return $this->hashPart($token);
		}
	}


    /**
     * Remove one level of line-leading tabs or spaces
     * @param $text
     * @return mixed
     */
    function outdent($text)
    {
		return preg_replace('/^(\t|[ ]{1,'.$this->tab_width.'})/m', '', $text);
	}


	# String length function for detab. `_initDetab` will create a function to 
	# hanlde UTF-8 if the default function does not exist.
	public $utf8_strlen = 'mb_strlen';

    /**
     * Replace tabs with the appropriate amount of space.
     * @param $text
     * @return mixed
     */
    function detab($text)
    {
		# For each line we separate the line in blocks delemited by
		# tab characters. Then we reconstruct every line by adding the 
		# appropriate number of space between each blocks.
		
		$text = preg_replace_callback('/^.*\t.*$/m',
			array(&$this, '_detab_callback'), $text);

		return $text;
	}

    /**
     * @param $matches
     * @return string
     */
    function _detab_callback($matches)
    {
		$line = $matches[0];
		$strlen = $this->utf8_strlen; # strlen function for UTF-8.
		
		# Split in blocks.
		$blocks = explode("\t", $line);
		# Add each blocks to the line.
		$line = $blocks[0];
		unset($blocks[0]); # Do not add first block twice.
		foreach ($blocks as $block) {
			# Calculate amount of space, insert spaces, insert block.
			$amount = $this->tab_width - 
				$strlen($line, 'UTF-8') % $this->tab_width;
			$line .= str_repeat(" ", $amount) . $block;
		}
		return $line;
	}

    /**
     *
     */
    function _initDetab() {
	#
	# Check for the availability of the function in the `utf8_strlen` property
	# (initially `mb_strlen`). If the function is not available, create a 
	# function that will loosely count the number of UTF-8 characters with a
	# regular expression.
	#
		if (function_exists($this->utf8_strlen)) return;
		$this->utf8_strlen = create_function('$text', 'return preg_match_all(
			"/[\\\\x00-\\\\xBF]|[\\\\xC0-\\\\xFF][\\\\x80-\\\\xBF]*/", 
			$text, $m);');
	}


    /**
     * Swap back in all the tags hashed by _HashHTMLBlocks.
     * @param $text
     * @return mixed
     */
    function unhash($text)
    {
		return preg_replace_callback('/(.)\x1A[0-9]+\1/',
			array(&$this, '_unhash_callback'), $text);
	}

    /**
     * @param $matches
     * @return mixed
     */
    function _unhash_callback($matches)
    {
		return $this->html_hashes[$matches[0]];
	}

}
