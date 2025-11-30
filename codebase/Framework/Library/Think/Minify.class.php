<?php
/**
 * Custom Minification
 *
 * 
 */

namespace think;

class Minify {
    public $htmlFilters = [
        // Remove HTML comments except IE conditions
        '/(?=<!--)(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s' => '',
        
		// Remove comments in the form /* */
		'/(?<!\S)\/\/\s*[^\r\n]*/' => '', //can be used
        
		// Shorten multiple white spaces
		'/\<script>s{2,}/' => ' ', //bad
	
        // Remove whitespaces between HTML tags
        '/>\s{2,}</' => '><', //Can be used
		
        // Collapse new lines
        //'/(\r?\n)/' => '', //Giving error
		
		//Remove  Comments
//		'/(?=<!--)([\s\S]*?)-->/' => '', //Bad
		//Remove blank lines 
		'/^\h*\v+/m'=> '',
		
		// remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
        '~//[a-zA-Z0-9 ]+$~m' => '',
		
		//remove empty lines (sequence of line-end and white-space characters)
        '/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
		//Remove inline css and js comments
	//	'/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/' =>'',
    ];

    /**
     * @param string $html
     *
     * @return string
     */
    public function html(string $html): string
    {
        $output = preg_replace(array_keys($this->htmlFilters), array_values($this->htmlFilters), $html);
		if($output==null){
			$output="We are currently having difficulties";
		}
        return $output;
    }
}