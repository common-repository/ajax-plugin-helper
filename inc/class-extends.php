<?php
class AjaxPluginHelper_Plugin_Installer_Skin extends WP_Upgrader_Skin {
        function feedback($string) {
                if ( isset( $this->upgrader->strings[$string] ) )
                        $string = $this->upgrader->strings[$string];

                if ( strpos($string, '%') !== false ) {
                        $args = func_get_args();
                        $args = array_splice($args, 1);
                        if ( !empty($args) )
                                $string = vsprintf($string, $args);
                }
                if ( empty($string) )
                        return;
                echo "<p>$string</p>\n";
        }
}
