<?php



/**

 * Plugin admin functions.

 */



// Add the admin menu.

add_action('admin_menu', 'dynamic_slider_plugin_add_admin_menu');



/**

 * Edit a slide.

 *

 * @param int $id

 * @param array $data

 */

function edit_dynamic_slider_slide($id, $data)

{

    global $wpdb;

    $table_name = $wpdb->prefix . 'dynamic_slider';



    // Handle image upload

    $image_url = '';

    if ($data['image'] && !empty($data['image']['tmp_name'])) {

        $upload_dir = wp_upload_dir();

        $image_name = sanitize_file_name($data['image']['name']);

        $image_path = $upload_dir['path'] . '/' . $image_name;



        if (move_uploaded_file($data['image']['tmp_name'], $image_path)) {

            $image_url = $upload_dir['url'] . '/' . $image_name;
        }
    }



    $sql = "UPDATE $table_name SET

    image = '{$image_url}',

    header = '{$data['header']}',

    paragraph = '{$data['paragraph']}',

    button1_label = '{$data['button1_label']}',

    button1_url = '{$data['button1_url']}',

    button2_label = '{$data['button2_label']}',

    button2_url = '{$data['button2_url']}'

    WHERE id = {$id};";



    $wpdb->query($sql);
}





/**

 * Get all the slides.

 *

 * @return array

 */

function get_dynamic_slider_slides()

{





    global $wpdb;

    $table_name = $wpdb->prefix . 'dynamic_slider';



    $sql = "SELECT * FROM $table_name;";



    $results = $wpdb->get_results($sql, ARRAY_A);



    return $results;
}





/**

 * Add a slide to the dynamic slider.

 *

 * @param string $header

 * @param string $paragraph

 * @param string $button1_label

 * @param string $button1_url

 * @param string $button2_label

 * @param string $button2_url

 * @return void

 */

function add_dynamic_slider_slide(
    $header,
    $paragraph,
    $button1_label,
    $button1_url,
    $button2_label,
    $button2_url,
    $image
) {

    global $wpdb;

    $table_name = $wpdb->prefix . 'dynamic_slider';


    $wpdb->insert(

        $table_name,

        array(

            'image' => $image,

            'header' => $header,

            'paragraph' => $paragraph,

            'button1_label' => $button1_label,

            'button1_url' => $button1_url,

            'button2_label' => $button2_label,

            'button2_url' => $button2_url,

        )

    );
}






/**

 * Delete a slide.

 *

 * @param int $id

 */

function delete_dynamic_slider_slide($id)

{

    global $wpdb;

    $table_name = $wpdb->prefix . 'dynamic_slider';



    $sql = "DELETE FROM $table_name WHERE id = {$id};";



    $wpdb->query($sql);
}



/**

 * Display the slider.

 */
