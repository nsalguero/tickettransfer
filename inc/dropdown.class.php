<?php
class PluginTickettransferDropdown extends Dropdown
{
    /**
     * Dropdown of values in an array
     *
     * @param string $name      select name
     * @param array  $elements  array of elements to display
     * @param array  $options   array of possible options:
     *    - value               : integer / preselected value (default 0)
     *    - display             : boolean / display or return string
     *    - rand                : specific rand if needed (default is generated one)
     *    - display_emptychoice : display empty choice (default false)
     *    - option_tooltips     : array / message to add as tooltip on the dropdown options. Use the same keys as for the $elements parameter, but none is mandotary. Missing keys will just be ignored and no tooltip will be added (default empty)
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function showFromArray($name, array $elements, $options = [])
    {
        $output = parent::showFromArray($name, $elements, $options);
        $output = str_replace("= fuzzy.match(", "= transfersearch.match(", $output);
        return $output;
    }
}
