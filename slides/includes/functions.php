<?php
// Includes the scripts.php file
require_once plugin_dir_path(__FILE__) . '/scripts.php';

function dynamic_slider_display()
{
?>
    <div class="slider">
        <ul class="slides">
            <?php
            $slides = get_dynamic_slider_slides();
            foreach ($slides as $slide) {
                echo '<li class="slide">';
                echo '<img src="' . esc_url($slide['image']) . '">';
                echo '<div class="overlay"></div>';
                echo '<div class="content">';
                echo '<h2>' . esc_html($slide['header']) . '</h2>';
                echo '<p>' . esc_html($slide['paragraph']) . '</p>';
                echo '<div class="buttons-cont">';
                echo '<div class="buttons">';
                echo '<a href="' . esc_url($slide['button1_url']) . '">' . esc_html($slide['button1_label']) . ' <svg xmlns="http://www.w3.org/2000/svg" width="25" height="20" viewBox="0 0 20 20" fill="none"><path d="M20 10.5L8.90625 21.625C8.65625 21.875 8.35917 22 8.015 22C7.67083 22 7.37417 21.875 7.125 21.625C6.875 21.375 6.75 21.0779 6.75 20.7338C6.75 20.3896 6.875 20.0929 7.125 19.8438L18.25 8.75H8.75C8.39583 8.75 8.09875 8.63 7.85875 8.39C7.61875 8.15 7.49917 7.85334 7.5 7.5C7.5 7.14584 7.62 6.84875 7.86 6.60875C8.1 6.36875 8.39667 6.24917 8.75 6.25H21.25C21.6042 6.25 21.9012 6.37 22.1412 6.61C22.3812 6.85 22.5008 7.14667 22.5 7.5V20C22.5 20.3542 22.38 20.6513 22.14 20.8913C21.9 21.1313 21.6033 21.2508 21.25 21.25C20.8958 21.25 20.5988 21.13 20.3588 20.89C20.1188 20.65 19.9992 20.3533 20 20V10.5Z" fill="#EB0008"></path></svg></i>';
                echo '</div>';
                echo '<div class="button2">';
                echo '<a href="' . esc_url($slide['button2_url']) . '"><i class="fas fa-display"></i>' . esc_html($slide['button2_label']) . '</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</li>';
            }
            ?>
        </ul>
    </div>
<?php
}

add_shortcode('dynamic_slider', 'dynamic_slider_display');


/**

 * Unit Test: Verify the functionality of the plugin.

 */

function dynamic_slider_unit_test()

{

    // Test adding a slide

    add_dynamic_slider_slide('Test Header', 'Test Paragraph', 'Button 1', 'https://example.com', 'Button 2', 'https://example.com', 'http://bodtops-realty.local/wp-content/uploads/2023/05/nupo-1-1.jpg');

    $slides = get_dynamic_slider_slides();

    $last_slide = end($slides);

    if ($last_slide['header'] != 'Test Header' || $last_slide['paragraph'] != 'Test Paragraph' || $last_slide['button1_label'] != 'Button 1' || $last_slide['button1_url'] != 'https://example.com' || $last_slide['button2_label'] != 'Button 2' || $last_slide['button2_url'] != 'https://example.com' || $last_slide['image'] != 'http://bodtops-realty.local/wp-content/uploads/2023/05/nupo-1-1.jpg') {

        echo 'Adding a slide failed.';
    }



    // Test deleting a slide

    $slide_id = $last_slide['id'];

    delete_dynamic_slider_slide($slide_id);

    $slides = get_dynamic_slider_slides();

    $slide_ids = array_column($slides, 'id');

    if (in_array($slide_id, $slide_ids)) {

        echo 'Deleting a slide failed.';
    }



    // Test updating a slide

    add_dynamic_slider_slide('Test Header', 'Test Paragraph', 'Button 1', 'https://example.com', 'Button 2', 'https://example.com', 'http://bodtops-realty.local/wp-content/uploads/2023/05/nupo-1-1.jpg');

    $slides = get_dynamic_slider_slides();

    $last_slide = end($slides);

    $slide_id = $last_slide['id'];

    update_dynamic_slider_slide($slide_id, 'Updated Header', 'Updated Paragraph', 'Updated Button 1', 'https://example.com/updated', 'Updated Button 2', 'https://example.com/updated', 'http://bodtops-realty.local/wp-content/uploads/2023/05/nupo-1-1.jpg');

    $slides = get_dynamic_slider_slides();

    $updated_slide = get_dynamic_slider_slide($slide_id);

    if ($updated_slide['header'] != 'Updated Header' || $updated_slide['paragraph'] != 'Updated Paragraph' || $updated_slide['button1_label'] != 'Updated Button 1' || $updated_slide['button1_url'] != 'https://example.com/updated' || $updated_slide['button2_label'] != 'Updated Button 2' || $updated_slide['button2_url'] != 'https://example.com/updated' || $updated_slide['image'] != 'http://bodtops-realty.local/wp-content/uploads/2023/05/nupo-1-1.jpg') {

        echo 'Updating a slide failed.';
    }



    // Test deleting all slides

    delete_all_dynamic_slider_slides();

    $slides = get_dynamic_slider_slides();

    if (!empty($slides)) {

        echo 'Deleting all slides failed.';
    }



    echo 'Unit test completed.';
}



// Uncomment the line below to run the unit test

// dynamic_slider_unit_test();


?>