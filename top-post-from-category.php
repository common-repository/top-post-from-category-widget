<?php
/*
Plugin Name: Top Post From Category Widget
Plugin URI: http://cjyabraham.com/projects/top-post-from-category-plugin/
Description: Sidebar widget that shows the top post from a particular category plus its thumbnail.  The excerpt from the post can be overridden.
Author: Chris Abraham
Version: 0.3
Author URI: http://cjyabraham.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St - 5th Floor, Boston, MA  02110-1301, USA.

*/


/**
 * Displays widget.
 *
 * Supports multiple widgets.
 *
 * @param array $args Widget arguments.
 * @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.
 */
function widget_tpfc( $args, $widget_args = 1 ) {
        extract( $args, EXTR_SKIP );
        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );

        // Data should be stored as array:  array( number => data for that instance of the widget, ... )
        $options = get_option('widget_tpfc');
        if ( !isset($options[$number]) )
                return;

        $category = $options[$number]['category'];
        $excerpt = $options[$number]['excerpt'];

        if (is_home())
          {
          $title = empty($options[$number]['title']) ? __('Top Post from Category') : $options[$number]['title'];

          # since this widget special style needs, we override the default
          # $before_widget value set in functions.php
          $before_widget = '<div class="widget" id="top-post">';

          echo $before_widget;
          echo $before_title . $title . $after_title;

          $my_query = new WP_Query("cat=$category&showposts=1");
          $my_query->the_post();

          $thumb = get_the_thumb();
          ?>
            <div class="post">
               <h4 class="post-title"><a href="<?php the_permalink(); ?>"> <?php the_title(); ?> </a></h4>
               <span class="byline">by  <?php the_author_posts_link(); ?></span>
               <?php if ($thumb){ ?>
               <div class="thumbnail"><img src="<?php echo $thumb; ?>" alt="<?php the_title(); ?>" /></div>
               <?php } ?>
               <p class="post-entry">
               <?php
                  if ($excerpt)
                    echo $excerpt;
                  else
                    the_excerpt_rss();
               ?>
               <a href="<?php the_permalink(); ?>">more &raquo;</a></p>
            </div>

          <?php
          echo $after_widget;
          }
}


/**
 * Displays form for a particular instance of the widget.
 *
 * Also updates the data after a POST submit.
 *
 * @param array|int $widget_args Widget number. Which of the several widgets of this type do we mean.
 */
function widget_tpfc_control( $widget_args = 1 ) {
        global $wp_registered_widgets;
        static $updated = false; // Whether or not we have already updated the data after a POST submit

        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );

        // Data should be stored as array:  array( number => data for that instance of the widget, ... )
        $options = get_option('widget_tpfc');
        if ( !is_array($options) )
                $options = array();

        // We need to update the data
        if ( !$updated && !empty($_POST['sidebar']) ) {
                // Tells us what sidebar to put the data in
                $sidebar = (string) $_POST['sidebar'];

                $sidebars_widgets = wp_get_sidebars_widgets();
                if ( isset($sidebars_widgets[$sidebar]) )
                        $this_sidebar =& $sidebars_widgets[$sidebar];
                else
                        $this_sidebar = array();

                foreach ( $this_sidebar as $_widget_id ) {
                        // Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
                        // since widget ids aren't necessarily persistent across multiple updates
                        if ( 'widget_tpfc' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
                                $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                                if ( !in_array( "tpfc-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed. "tpfc-$widget_number" is "{id_base}-{widget_number}
                                        unset($options[$widget_number]);
                        }
                }

                foreach ( (array) $_POST['widget-tpfc'] as $widget_number => $widget_tpfc_instance ) {
                        // compile data from $widget_tpfc_instance
                        if ( !isset($widget_tpfc_instance['category']) && isset($options[$widget_number]) ) // user clicked cancel
                                continue;
                        $category = wp_specialchars( $widget_tpfc_instance['category'] );
                        $title = strip_tags(stripslashes($widget_tpfc_instance['title']));
                        $excerpt = stripslashes($widget_tpfc_instance['excerpt']);
                        $options[$widget_number] = array( 'category' => $category, 'title' => $title, 'excerpt' => $excerpt );  // Even simple widgets should store stuff in array, rather than in scalar
                }

                update_option('widget_tpfc', $options);

                $updated = true; // So that we don't go through this more than once
        }


        // Here we echo out the form
        if ( -1 == $number ) { // We echo out a template for a form which can be converted to a specific form later via JS
                $category = '';
                $number = '%i%';
        } else {
                $category = attribute_escape($options[$number]['category']);
                $title = htmlspecialchars($options[$number]['title'], ENT_QUOTES);
                $category = htmlspecialchars($options[$number]['category'], ENT_QUOTES);
                $excerpt = stripslashes($options[$number]['excerpt']);
                if (!$title)
                  $title = 'Top Post from Category';

        }

        // The form has inputs with names like widget-tpfc[$number][category] so that all data for that instance of
        // the widget are stored in one $_POST variable: $_POST['widget-tpfc'][$number]
?>
    <p>
       <label for="widget-tpfc-title-<?php echo $number; ?>"><?php echo __('Title:'); ?>
       <input style="width: 200px;" id="widget-tpfc-title-<?php echo $number; ?>" name="widget-tpfc[<?php echo $number; ?>][title]" type="text" value="<?php echo $title; ?>" />
        </label>
    </p>
    <p>
       <label for="widget-tpfc-category-<?php echo $number; ?>"><?php echo __('Category:', 'widgets'); ?>
       <select id="widget-tpfc-category-<?php echo $number; ?>" name="widget-tpfc[<?php echo $number; ?>][category]?>">
          <?php $categories = get_categories(); ?>
       <?php foreach($categories as $cat):?>
           <option <?php if ($cat->term_id==$category) echo 'selected="selected"';?> value="<?php echo $cat->term_id;?>"><?php echo $cat->name;?></option>
       <?php endforeach;?>
       </select>
       </label>
    </p>
    <p style="text-align:left;">
       <label for="widget-tpfc-excerpt-<?php echo $number; ?>"><?php echo __('Excerpt:'); ?>
       <textarea style="width: 100%; height: 150px;" id="widget-tpfc-excerpt-<?php echo $number; ?>" name="widget-tpfc[<?php echo $number; ?>][excerpt]?>"><?php echo $excerpt; ?></textarea>
        </label>
    </p>
    <input type="hidden" id="widget-tpfc-submit-<?php echo $number; ?>" name="widget-tpfc[<?php echo $number; ?>][submit]" value="1" />

<?php
}

/**
 * Registers each instance of our widget on startup.
 */
function widget_tpfc_register() {
        if ( !$options = get_option('widget_tpfc') )
                $options = array();

        $widget_ops = array('classname' => 'widget_tpfc', 'description' => __('Shows the top post from a particular category.'));
        $control_ops = array('width' => 400, 'height' => 350, 'id_base' => 'tpfc');
        $name = __('Top Post From Category');

        $registered = false;
        foreach ( array_keys($options) as $o ) {
                // Old widgets can have null values for some reason
                if ( !isset($options[$o]['category']) ) // we used 'category' above in our exampple.  Replace with with whatever your real data are.
                        continue;

                // $id should look like {$id_base}-{$o}
                $id = "tpfc-$o"; // Never never never translate an id
                $registered = true;
                wp_register_sidebar_widget( $id, $name, 'widget_tpfc', $widget_ops, array( 'number' => $o ) );
                wp_register_widget_control( $id, $name, 'widget_tpfc_control', $control_ops, array( 'number' => $o ) );
        }

        // If there are none, we register the widget's existance with a generic template
        if ( !$registered ) {
                wp_register_sidebar_widget( 'tpfc-1', $name, 'widget_tpfc', $widget_ops, array( 'number' => -1 ) );
                wp_register_widget_control( 'tpfc-1', $name, 'widget_tpfc_control', $control_ops, array( 'number' => -1 ) );
        }
}

// This is important
add_action( 'widgets_init', 'widget_tpfc_register' );


function get_the_thumb() { //always inside the loop
  global $post;

  $attargs = array(
                   'post_type' => 'attachment',
                   'numberposts' => null,
                   'post_status' => null,
                   'post_parent' => $post->ID
                   );

  $attachments = get_posts($attargs);

  if ($attachments)
    return wp_get_attachment_thumb_url($attachments[0]->ID);

  return '';
}
?>
