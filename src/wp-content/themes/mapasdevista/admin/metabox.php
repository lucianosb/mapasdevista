<?php 


add_action( 'add_meta_boxes', 'mapasdevista_add_custom_box' );


add_action( 'save_post', 'mapasdevista_save_postdata' );


function mapasdevista_add_custom_box() {

        // Lets check which post types have to have a map
        
        // If it is displayed in at least one map, there will be a metabox to place the post on the map
        
        $maps = mapasdevista_get_maps();
        
        $post_types = array();
        
        foreach ($maps as $map) {
            if (is_array($map['post_types']))
                foreach ($map['post_types'] as $p_type)
                    array_push($post_types, $p_type);
        }
        
        $post_types = array_unique($post_types);
        
        foreach ($post_types as $post_type)
            add_meta_box( 'mapasdevista_metabox', __( 'Place it on the map', 'mapasdevista' ), 'mapasdevista_metabox_map', $post_type );
        
        
        // And there will also be one meta box for each map that uses an image as a map.
        foreach ($maps as $map) {
        
            if ($map['type'] == 'image') {
                if (is_array($map['post_types']))
                    foreach ($map['post_types'] as $p_type)
                        add_meta_box( 'mapasdevista_metabox', sprintf( __( 'Place it on the map %s%', 'mapasdevista' ), $map['name'] ), 'mapasdevista_metabox_image', $p_type );
            
            }
        
        }
        
}


function mapasdevista_metabox_map() {
    // Use nonce for verification
    wp_nonce_field( plugin_basename( __FILE__ ), 'mapasdevista_noncename' );
    ?>
    <fieldset>
        <label for="mpv_lat"><?php _e('Latitude', 'mpv');?>:</label>
        <input type="text" class="medium-field" name="mpv_lat" id="mpv_lat"/>
        
        <label for="mpv_lon"><?php _e('Longitude', 'mpv');?>:</label>
        <input type="text" class="medium-field" name="mpv_lon" id="mpv_lon"/>
        
        <input type="button" id="mpv_load_coords" value="Exibir"/>
    </fieldset>
    <div id="mpv_canvas"></div>
    <fieldset>
        <label for="mpv_search_address"><?php _e('Search address', 'mpv');?>:</label>
        <input type="text" id="mpv_search_address" class="large-field"/>
    </fieldset>
        
    <script type="text/javascript">
    (function($) {
        var map_options = {
            'zoom':14,
            'scrollwheel':true,
            'draggableCursor':'default',
            'center': new google.maps.LatLng(-23.56367, -46.65372),
            'mapTypeId': google.maps.MapTypeId.ROADMAP
            }
        var googlemap = new google.maps.Map(document.getElementById("mpv_canvas"), map_options);
        var googlemarker = null;

        function fill_fields(lat, lng) {
            $("#mpv_lat").val(lat);
            $("#mpv_lon").val(lng);
        }
        
        function load_post_marker(lat, lng) {
            try{
                lat = parseFloat(lat);
                lng = parseFloat(lng);
                if(lat && lng) {
                    if(googlemarker) {
                        googlemarker.setPosition(new google.maps.LatLng(lat, lng));
                    }else{
                        fill_fields(lat, lng);
                        googlemarker = new google.maps.Marker({
                            map: googlemap,
                            draggable: true,
                            position: new google.maps.LatLng(lat, lng)
                        });
                        googlemap.panTo(googlemarker.getPosition());
                        $('<input type="button" style="position:absolute;top:0.5em;left:3em">')
                            .val('Center map in marker')
                            .appendTo("#mpv_canvas")
                            .click(function(){googlemap.panTo(googlemarker.getPosition());});
                    }
                }
            } catch(e) {
                if(document.location.href.match(/^https?:\/\/localhost/)){
                    console.log(e);
                }
            }
            return false;
        }
        
        if(!load_post_marker($("#mpv_lat").val(), $("#mpv_lon").val())) {
            var clicklistener = google.maps.event.addListener(googlemap, 'click', function(event) {
                place_marker(event.latLng);
            });
        }
        
        var place_marker = function(location) {
            if(googlemarker === null) {                
                load_post_marker(location.lat(), location.lng());
            }
            if(clicklistener){
                google.maps.event.removeListener(clicklistener);
            }
            // específico do google maps
            google.maps.event.addListener(googlemarker, 'drag', function(e) {
                fill_fields(e.latLng.lat(), e.latLng.lng());
            });
            google.maps.event.addListener(googlemap, 'click', function(e) {
                googlemarker.setPosition(e.latLng);
                fill_fields(e.latLng.lat(), e.latLng.lng());
            });
        }
        
        var geocoder = new google.maps.Geocoder();
        
        function geocode_callback(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                googlemap.setCenter(results[0].geometry.location);
                if(googlemarker) {
                    googlemarker.setPosition(results[0].geometry.location)
                } else {              
                    googlemarker = new google.maps.Marker({
                        map: googlemap,
                        draggable: true,
                        position: results[0].geometry.location
                    });
                }
            }
        }
        
        $("#mpv_search_address").keypress(function(e){
            if(e.charCode===13){
                geocoder.geocode({'address': $(this).val()}, geocode_callback);
                return false;
            }
        });
        jQuery("#mpv_load_coords").click(function(){load_post_marker($("#mpv_lat").val(), $("#mpv_lon").val())});
        $(document).ready(function(){$("#mpv_canvas").resizable({ handles: 's'})});
    })(jQuery);
    </script>
    <?php
}

/* When the post is saved, saves our custom data */
function mapasdevista_save_postdata($post_id) {
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return;

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times

    if ( !wp_verify_nonce( $_POST['mapasdevista_noncename'], plugin_basename( __FILE__ ) ) )
            return;

    
    // Check permissions
    global $wp_post_types;
    
    $cap = $wp_post_types[$_POST['post_type']]->cap->edit_post;
    
    if ( !current_user_can( $cap, $post_id ) )
        return;
    
    // save

    
}
