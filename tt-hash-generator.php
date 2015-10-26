<?php

class TT_Hash_Generator {

    public $location;

    public function __construct($location = false) {

        if (!empty($location)) {
            $this->location = $location;
        }

    }

    public function get_hash() {

        $hash = $this->get_hash_vars();
        $hash['location'] = $this->location;

        return md5(serialize($hash));

    }

    public function get_hash_vars() {

        global $post;
        global $wp_query;
        $return_vars = array();

        //Single
        if (is_singular())
        {
            $return_vars['post_id'] = $post->ID;
            $return_vars['post_type'] = $post->post_type;

            return $return_vars;
        }

        //Front Page
        if (is_front_page())
        {
            $return_vars['front_page'] = 'front_page';

            return $return_vars;
        }

        //Blog Index
        if (is_home())
        {
            $return_vars['posts_home'] = 'posts_home';

            return $return_vars;
        }

        //Archive
        if (is_archive())
        {
            //Custom Post type
            if (is_post_type_archive()) {

                $return_vars['archive_type'] = 'post_type';
                $return_vars['post_type'] =  $wp_query->query['post_type'];

                return $return_vars;

            }

            //Category
            if (is_category()) {

                $return_vars['archive_type'] = 'category';
                $return_vars['post_type'] = $post->post_type;
                $return_vars['category'] = $wp_query->query['category_name'];

                return $return_vars;

            }

            //Tag Archive
            if (is_tag()) {

                $return_vars['archive_type'] = 'tag';
                $return_vars['post_type'] = $post->post_type;
                $return_vars['tag'] = $wp_query->query['tag'];

                return $return_vars;

            }

            //Taxonomy
            if (is_tax()) {

                $return_vars['archive_type'] = 'taxonomy';
                $return_vars['taxonomy'] = $wp_query->query_vars['taxonomy'];
                $return_vars['term'] = $wp_query->query_vars['term'];

                return $return_vars;

            }


            //Date Archive
            if (is_date()) {

                $return_vars['archive_type'] = 'date';
                $return_vars['post_type'] = $post->post_type;
                $return_vars['year'] = $wp_query->query_vars['year'];
                $return_vars['month'] = $wp_query->query_vars['monthnum'];

                return $return_vars;

            }

            //Author
            if (is_author()) {

                $author = false;

                if ( $author_id = get_query_var( 'author' ) ) {
                    $author = get_user_by( 'id', $author_id );
                }

                $return_vars['archive_type'] = 'author';
                $return_vars['author_id'] = $author;

                return $return_vars;
            }

        }

        //Comments Popup Page
        if (is_comments_popup())
        {
            return array('comments_popup' => 'comments_popup');
        }

        //Search Results
        if (is_search())
        {
            return array('search_page' => 'search');
        }

        //404
        if (is_404())
        {
            return array('fourzerofour' => '404');
        }

        if (is_admin()) {

            $return_vars['post_id'] = $post->ID;
            $return_vars['post_type'] = $post->post_type;

            return $return_vars;

        }

    }

}