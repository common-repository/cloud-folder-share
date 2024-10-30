<?php

/**
 * Plugin Name: Cloud Folder Share
 * Plugin URI: http://www.hyno.ok.pe/wp-plugins/cloud-folder-share/
 * Description: Permite la inclusion de carpetas compartidas en Google Drive, Dropbox en las entrada del Blog.
 * Version: 0.1
 * Author: Antonio Salas (Hyno)
 * Author URI: http://www.hyno.ok.pe
 * License:     GNU General Public License
 */
if (!function_exists("file_get_html")) {
    include_once('simple_html_dom.php');
}
if (!class_exists("CloudFolderShare")) {

    class CloudFolderShare {

        public $driveList;

        function __construct() {
            include_once 'cloud_drives.php';
            $this->driveList = $drive;
        }

        public function load_plugin_textdomain() {
            //Cargamos paquete de lenguajes
            load_plugin_textdomain('CFS-Hyno', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        function activate() {
            //Agregar Opciones a la BD
            add_option("CFS_show_as", 'lista');
            add_option("CFS_cloud", 'google');
            add_option("CFS_height", '250');
        }

        function deactivate() {
            //Quitar Opciones a la BD
            delete_option("CFS_show_as");
            delete_option("CFS_cloud");
            delete_option("CFS_height");
        }

        function add_admin_page() {
            add_submenu_page('options-general.php', 'Cloud Folder Share - By Hyno', 'Cloud Folder Share', 10, __file__, array(&$this, 'admin_page'));
        }

        function admin_page() {
            // update settings
            if (isset($_POST['CFS_Save'])) {
                $nube = $_POST['CFS_cloud'];
                $alto = $_POST['CFS_height'];

                if ($alto > 100) {
                    update_option("CFS_height", $alto);
                } else {
                    $error = array(true, "Error: Alto minimo 100px");
                }
                if ($nube != '') {
                    update_option("CFS_cloud", $nube);
                } else {
                    $error = array(true, "Error: Error en Seleccion de Almacenamiento en Nube");
                }
                if (!$error[0]) {
                    echo "<div id=\"message\" class=\"updated fade\"><p><strong>";
                    _e('Opciones de "Cloud Folder Share" Actualizados.', 'CFS-Hyno');
                    echo "</strong></p></div>";
                } else {
                    echo "<div id=\"message\" class=\"error fade\"><p><strong>";
                    _e('No pudimos actualizar sus datos.', 'CFS-Hyno');
                    echo "<br />";
                    echo $error[1];
                    echo "</strong></p></div>";
                }
            }
            require_once ('admin_page.php');
        }

        function bbcodeShortcode($atts, $url) {
            extract(shortcode_atts(array(
                "cloud" => get_option('CFS_cloud'),
                //"show_as" => get_option('CFS_show_as'),
                "name" => false
                            ), $atts));
            if ($url == '') {
                echo "<div class=\"hyno\">";
                echo "<div class=\"error fade\"><span>";
                _e('Error en URL en la carpeta compartida.', 'CFS-Hyno');
                echo "</span></div>";
                echo "</div>";
            } else {
                return $this->getFolder($url, $cloud, $name);
            }
        }

        function getFolder($url, $cloud, $nombreCarpeta) {
            if ($cloud != "") {
                $domain = preg_replace('/\/\//', '/', $url);
                $domain = explode("/", $domain);
                if (preg_match("/$cloud/", $domain[1])) {
                    //echo $domain[1];
                    $content = $this->fetch_url($url);
                    if ($content != "") {

                        $htmlCode = str_get_html($content);
                        $objReadDrives = new ReadDrives($url, $htmlCode, $nombreCarpeta);

                        switch ($cloud) {
                            case 'dropbox':
                                $respuesta = "<div id='CFS_ContenFolder' class='flip-contents flip-list-view'>";
                                $respuesta .= $objReadDrives->Dropbox();
                                $respuesta .= "</div>";
                                break;
                            case 'google':
                                $respuesta = "<div id='CFS_ContenFolder' class='flip-contents flip-list-view'>";
                                $respuesta .= $objReadDrives->Google();
                                $respuesta .= "</div>";
                                break;
                        }

                        //echo "<textarea cols=80 rows=10>";
                        //echo $respuesta;
                        //echo "</textarea>";
                    } else {
                        $error = true;
                    }
                } else {
                    $error = true;
                }
            } else {
                $error = true;
            }
            if (!$error) {
                return $respuesta;
            }
        }

        function fetch_url($url) {
            return ($fp = fopen($url, 'r')) ? stream_get_contents($fp) : false;
        }

        function regStyles_Scripts() {
			if(isset($_GET["page"])){
			//echo "<h1>".$_GET["page"]."</h1>";
			if($_GET["page"] == 'cloud-folder-share/cloud-folder-share.php'){
            wp_enqueue_style('CFS-style', plugins_url("css/hyno-styles.css", __FILE__));
            wp_enqueue_style('msdropdown_dd', plugins_url("css/msdropdown/dd.css", __FILE__));
            wp_enqueue_script('msDropdown', plugins_url('js/jquery.dd.min.js', __FILE__));
            wp_enqueue_script('CFS-script', plugins_url('js/scripts-hyno.js', __FILE__));
            //<link rel="stylesheet" type="text/css" href="css/msdropdown/dd.css" />
			}
			}
        }

        function regButtonMCE($plugin_array) {
            $url_CFS = plugins_url('/js/CloudFolderShare.js', __FILE__);
            $plugin_array['CloudFolderShare'] = $url_CFS;
            return $plugin_array;
        }

        function addButtonMCE($buttons) {
            array_push($buttons, "|", "CloudFolderShare");
            return $buttons;
        }

    }

}
if (class_exists("CloudFolderShare")) {
    $CFS = new CloudFolderShare();
    include_once 'drives.php';
    //$bvwidget = new DropBoxFolderShareWidget();
}
if (isset($CFS)) {
    //Agregar Boton a MCE
    add_filter('mce_external_plugins', array(&$CFS, 'regButtonMCE'));
    add_filter('mce_buttons', array(&$CFS, 'addButtonMCE'), 0);

    //Agregar Estilo
    add_filter('the_posts', 'CFS_styles_and_scripts');

    //Agrega la Opcion de configuracion en el menu
    add_action('admin_menu', array(&$CFS, 'add_admin_page'));
    add_action('admin_menu', array(&$CFS, 'regStyles_Scripts'));

    //BBCode
    add_shortcode('CFS', array(&$CFS, 'bbcodeShortcode'));

    // activate/deactivate
    register_activation_hook(__file__, array(&$CFS, 'activate'));
    register_deactivation_hook(__file__, array(&$CFS, 'deactivate'));
}







if (!function_exists('CFS_styles_and_scripts')) {

    function CFS_styles_and_scripts($posts) {
        if (empty($posts))
            return $posts;

        $shortcode_found = false; // usamos shortcode_found para saber si nuestro plugin esta siendo utilizado

        foreach ($posts as $post) {

            if (stripos($post->post_content, 'CFS')) { //shortcode a buscar
                $shortcode_found = true; // bingo!
                break;
            }
        }
        if ($shortcode_found) {
            // enqueue
            wp_enqueue_script('jquery');
            wp_enqueue_style('CFS-style', plugins_url("css/hyno-styles.css", __FILE__)); //la ruta de nuestro css
            //wp_enqueue_script('CFS-script', plugins_url('js/scripts-hyno.js', __FILE__)); //en caso de necesitar la ruta de nuestro script js
        }

        return $posts;
    }

}
?>
