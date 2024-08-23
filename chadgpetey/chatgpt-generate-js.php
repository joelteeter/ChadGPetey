<?php
// Add JavaScript code to handle ChatGPT reply
function add_chatgpt_js_plugin_page() {
    $openai_api_key = get_option( 'api_key' );
    $openai_model = get_option( 'chat_model' );
    $openai_max_tokens = get_option( 'max_tokens' );
    $openai_temperature = get_option( 'temperature' );
    $openai_post_status = get_option( 'post_status' );
    $author_id = get_option( 'author_id' );
    ?>
    <script>
    jQuery(document).ready(function($) {     
        
        

        jQuery("#generate-content-form-submit").on( 'click', function(e) {
            $('#request-spinner').show();
            e.preventDefault(); 
            search_term = jQuery('#chad-prompt').val();
            post_title = jQuery('#post-title').val();
            post_id = jQuery(this).attr("data-post_id");       
            nonce = jQuery(this).attr("data-nonce");

            return jQuery.ajax({
                type : "post",
                dataType : "json",
                url : myAjax.ajaxurl,
                data : {
                    action: "check_title_duplicate", 
                    post_title : post_title,
                    nonce: nonce},
                success: function(response) {

                    console.log('the wp ajax request to check title was successful');
                    console.log(response);
                },
                error: function(xhr, status, error) {
                    // Handle AJAX error
                    console.log('this post already exists!');

                    // Hide the spinner empty the result
                    jQuery('#request-spinner').hide();
                    jQuery('#put-response-here').html("<div>There already exists a post with this title</div>");
                }
            }).then(function(response) {
                console.log('response is');
                console.log(response);
                console.log('response data is');
                console.log(response.data);
                console.log('response success is');
                console.log(response.success);
                if(response.success || response === "success") {
                    jQuery.ajax({
                        type: 'get',
                        dataType: 'json',
                        url: "https://api.openverse.org/v1/images/?q=%22"+search_term+"%22",
                        success: function(response) {
                            console.log('the creative commons request was successful');
                            var images = [];
                            var imgs = 0;
                            var featured_image = null;
                            if(response.result_count > 0) {
                                featured_image = {
                                            url: response.results[0].url,
                                            attribution: response.results[0].attribution,
                                            title: response.results[0].title,
                                            alt: "An image of "+search_term,
                                            element: "<img src='"+response.results[0].url+"' alt='an image of "+search_term+"' >"
                                        };
                                if(response.result_count > 6 ) {
                                    imgs = 6;
                                } else {
                                    imgs = response.result_count;
                                }
                                for(var i = 1; i < imgs; i++) {
                                    images.push(
                                        {
                                            url: response.results[i].url,
                                            attribution: response.results[i].attribution,
                                            title: response.results[i].title,
                                            alt: "An image of "+search_term,
                                            element: "<img src='"+response.results[i].url+"' alt='an image of "+search_term+"' >"
                                        });
                                }
                                openai_reply(1, featured_image, images, search_term, post_title)  

                            } else {
                                //for now just generate the featured image
                                openai_generate_images(1, search_term, post_title);
                            }
                            
                        },
                        error: function(xhr, status, error) {
                            // Handle AJAX error
                            console.log('the creative commons request failed');

                            // Hide the spinner empty the result
                            jQuery('#request-spinner').hide();
                            jQuery('#put-response-here').html("<div>ERROR trying to retrievie creative commons images</div>");

                        }
                    });  
                }  else {
                    // Hide the spinner empty the result
                    jQuery('#request-spinner').hide();
                    jQuery('#put-response-here').html("<div>There already exists a post with this title</div>");
                }         
                
            });
        });

        function openai_generate_images(n, prompt, post_title) {
            var package = {
                prompt: "photo of "+ prompt+", Nikon D810, Sigma 24mm f/8",
                n: n,
                size: "1024x1024"
            }
            var apiKey = "<?php echo esc_attr( $openai_api_key ); ?>";
            return $.ajax({
                type: "POST",
                url: "https://api.openai.com/v1/images/generations",
                headers: {
                    "Content-Type": "application/json",
                    "Authorization": "Bearer " + apiKey
                },
                data: JSON.stringify(package),
                success: function(response) {
                    console.log('request for ai images successful');
                },
                error: function(response) {
                    console.log('request for ai images failed');

                    // Hide the spinner empty the result
                    jQuery('#request-spinner').hide();
                    jQuery('#put-response-here').html("<div>ERROR trying to retrievie ai images</div>");
                }
            }).then(function(response) {
                var images = [];
                var featured_image = '';
                if(response['data'].length > 0) {
                    featured_image = {
                                    url: response['data'][0]['url'],
                                    attribution: "Photo generated by Chad G Petey",
                                    title: "Photo of "+search_term,
                                    alt: "An image of "+search_term,
                                    element: "<img src='"+response['data'][0]['url']+"' alt='an image of "+search_term+"' >"
                                }
                    
                    ;
                }
                if(response['data'].length > 1) {
                    for(var i = 1; i <= response['data'].length; i++) {
                        images.push(
                            {
                                url: response['data'][i].url,
                                attribution: "Photo generated by Chad G Petey",
                                title: "Photo of "+search_term,
                                alt: "An image of "+search_term,
                                element: "<img src='"+response['data'][i].url+"' alt='an image of "+search_term+"' >"
                            });
                    }
                }
                openai_reply(1, featured_image, images, search_term, post_title);
            });
        }
        

        function openai_reply(comment_id, featured_image, post_images, search_term, post_title) { 
            if(!post_images || post_images.length < 1) {
                post_images.push(featured_image);
            };
      
            rowData = $('#inline-'+comment_id);

            const SUBJECT = search_term;
            comment_text = "Write a travel blog entry about "+SUBJECT;
            editRow = $('#replyrow');
            $( '#replysubmit .spinner' ).addClass( 'is-active' );
            apiKey = "<?php echo esc_attr( $openai_api_key ); ?>";
            openai_model = "<?php echo esc_attr( $openai_model ); ?>";
            openai_max_tokens = "<?php echo esc_attr( $openai_max_tokens ); ?>";
            openai_temperature = "<?php echo esc_attr( $openai_temperature ); ?>"; 
            openai_post_status = "<?php echo esc_attr( $openai_post_status ); ?>"; 
            //TODO: set up correct package for different models
            //TODO: set up correct endpoints for different models
            var endpoint = "";
            var package = {
                  "model": openai_model,                  
                  "max_tokens": Number(openai_max_tokens),
                  "temperature": Number(openai_temperature)
                } 
            switch(openai_model) {
                case "gpt-4":
                    endpoint = "/v1/chat/completions";
                    package['messages'] = [
                        {
                           "role": "user",
                           "content": "I am Chad G Petey"
                        },
                        {
                           "role": "user",
                           "content": comment_text
                        }
                    ];
                    break;
                case "text-davinci-003":
                    endpoint = "/v1/completions";
                    package["prompt"] = comment_text;
                    break;
                default:
                    package['messages'] = [
                        {
                           "role": "user",
                           "content": "I am Chad G Petey"
                        },
                        {
                           "role": "user",
                           "content": comment_text
                        }
                    ];
            }
         
            return $.ajax({
                type: "POST",
                url: "https://api.openai.com"+endpoint,
                headers: {
                  "Content-Type": "application/json",
                  "Authorization": "Bearer " + apiKey
                },
                data: JSON.stringify(package),
                success: function(response) {
                  console.log('the ai content request was successful');
                },
                error: function(xhr, status, error) {
                    
                    // Handle AJAX error
                    console.log('the ai contentent request failed');

                    // Hide the spinner empty the result
                    jQuery('#request-spinner').hide();
                    jQuery('#put-response-here').html("<div>ERROR trying to retrievie ai content</div>");
                }
            }).then(function(response) {
               var sampleResponse = {
                "choices": [
                    {
                    "index": 0,
                    "message": {
                        "role": "assistant",
                        "content": "Title: Delving into Designer Dungeons: A Unique Adventure in Boise\n\nGreetings fellow wanderlust enthusiasts and game aficionados alike! Today we embark on a one-of-a-kind escapade, away from the traditional scenic spots and exotic food hunts that usually characterize our thrilling journeys. Pack your Imagination and ready yourselves, as we journey into the magical realm of \"Designer Dungeons,\" nestled hidden in the charming city of Boise, Idaho.\n\nNestled in an industrial section of Boise’s downtown, Designer Dungeons may not be on the conventional tourist’s route, but beneath its unassuming exterior exists a world of boundless creativity and immersive gaming. It's an experience that guarantees to fire your imagination, challenge your wits, and transport you to fantastical worlds created by some of the best dungeon designers in the whole Treasure Valley.\n\nThe first thing you notice as you step inside is the aura of intrigue and adventure hanging in the air. The dimmed lights and brick walls decked out with maps, weapon replicas, and fantasy art sets the tone impeccably. Designer Dungeons is Boise’s hidden gem that appeals to both the hard-bitten gamer and the novice, offering a fascinating mix of tabletop gaming, miniature terrain building, and fantasy-themed escape rooms.\n\nCue up your favorite squad and dive into one of the customizable dungeons. Arm yourselves with miniature weapons, craft your heroic or villainous persona, and wrestle your way through hordes of mythical beasts, fiendish traps, and enigmatic puzzles under the watchful eye and narrative flourishes of a dedicated Dungeon Master. The tantalizing unpredictability of each game guarantees unique experiences and stories every time you draw your miniature swords.\n\nWhat makes Designer Dungeons stand out, however, is their dedication to crafting playable art. From sprawling castle keeps to claustrophobic caverns, each piece of terrain is meticulously handcrafted, boasting an incredible level of detail and realism. You can't help but admire the painted depictions of stone, wood, and foliage, artfully designed to elevate your gaming encounter. \n\nBeyond providing immersive gaming, Designer Dungeons is also a hub for community events and learning new skills. Their workshops can turn you into an amateur terrain-crafter, ready to recreate your fantasy worlds. There’s a heartwarming sense of camaraderie here as people huddle over tables painting tiny figures or putting the finishing touches on a snow-cap scenery.\n\nRemember to take a break from battling dragons and warlocks to visit their cozy cafe. The aroma of freshly brewed coffee, paired with a delicious selection of snacks and deserts, makes for the perfect pit stop between epic quests. And don't miss the shelves laden with fantasy literature and graphic novels; they are a book lover's delight, ready to take your imaginative experience even further.\n\nIn an era of digitalization, a visit to Designer Dungeons serves as a stark reminder of the power and allure of imagination and in-person interaction. It's a unique escape offering a novel way to experience Boise beyond its stunning landscapes and craft breweries. \n\nWhether you are an enthusiast of Dungeons & Dragons, a lover of detailed miniatures, or just a curious traveler seeking new experiences, the Designer Dungeons is a must-visit on your Boise adventure-list. This unusual destination whispers tales of valor and adventure- all it needs is a band of fearless adventurers. So, are you ready to take the plunge?\n\nUntil we cross paths in our next adventure - I bid you happy travels!"
                    },
                    "finish_reason": "stop"
                    }
                ]
               };
                var choices = response.choices;

                if( choices.length > 0) {
                    var choice = choices[0];
                    var reply_text = null;
                    //davici-3
                    if(choice.text) {
                        reply_text = choice.text;
                    }
                    //gpt-4
                    else if(choice.message && choice.message.content) {
                        reply_text = choice.message.content;
                    }


                    /*
                    * split up content into paragraphs array
                    * 

                    */

                    var post_paragraphs = reply_text.split(/\r?\n/);

                    reply_text = "<!-- wp:paragraph --><p>" + reply_text;
                    var text = reply_text.replace(/(?:\r\n\n|\r|\n\n)/g, '</p><!-- /wp:paragraph --><br class="chad-break" \/><div class="img-wrapper"></div>');
                    text = text+'</p><!-- /wp:paragraph -->';
                    jQuery("#put-response-here").append('<div id="chad-text">'+text+'</div>');
                    var paragraphCount = jQuery(".img-wrapper").length;
                    for(var a = 1; a < paragraphCount; a++){
                        if(a < post_images.length) {
                            var img = jQuery(post_images[a].element)
                            jQuery(".img-wrapper")[a].append(img[0]);
                        }
                    }
                    post_content = jQuery("#chad-text")[0].outerHTML;
                }
                counties = jQuery('#counties').val();
                var selected_category = jQuery( "#category option:selected" ).val();
                var selected_region = jQuery( "#region option:selected" ).val();

                return jQuery.ajax({
                    type : "post",
                    dataType : "json",
                    url : myAjax.ajaxurl,
                    data : {
                        action: "chad_post", 
                        post_content : post_paragraphs, 
                        post_title : post_title,
                        counties : counties,
                        selected_category : selected_category ? selected_category : "0",
                        selected_region : selected_region ? selected_region : "0",
                        featured_image : featured_image['url'],
                        post_images: post_images, 
                        nonce: nonce},
                    success: function(response) {

                        console.log('the wp ajax request was successful');
                        $('#request-spinner').hide();
                    },
                    error: function(xhr, status, error) {
                        // Handle AJAX error
                        console.log("chad_post response");
                        console.log(xhr);
                        console.log(status);
                        console.log(error);
                        console.log('the wp ajax request failed');

                        // Hide the spinner empty the result
                        jQuery('#request-spinner').hide();
                        jQuery('#put-response-here').html("<div>ERROR trying to post</div>");
                    }
                }).then(function(response) {
                    console.log('post is created!')
                });
            });
        }
    });
 
     
    </script>
    <?php
}
add_action('wp_footer', 'add_chatgpt_js_plugin_page');