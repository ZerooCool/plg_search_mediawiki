<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Search.Mediawiki
 *
 * @copyright   Copyright 2015 Cem Aydin
 * @license     GNU/GPL
 */

// based on example from: https://docs.joomla.org/J3.x:Creating_a_search_plugin

//To prevent accessing the document directly, enter this code:
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// NOTE: commenting out for now
// Require the component's router file (Replace 'nameofcomponent' with the [1] (wrap)
// component your providing the search for
//require_once JPATH_SITE .  '/components/nameofcomponent/helpers/route.php';

/**
 * All functions need to get wrapped in a class
 *
 * The class name should start with 'PlgSearch' followed by the name of the plugin. [1] (wrap)
 * Joomla calls the class based on the name of the plugin, so it is very important [1] (wrap)
 * that they match
 */
class PlgSearchMediawiki extends JPlugin
{

    // INFO: in the other plugins this is not used anymore e.g. contacts, categories
    //       instead below is used
    /**
     * Constructor
     *
     * @access      protected
     * @param       object  $subject The object to observe
     * @param       array   $config  An array that holds the plugin configuration
     * @since       1.6
     */
    /*public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }*/
    protected $autoloadLanguage = true;

    // Define a function to return an array of search areas. Replace 'nameofplugin'
    // with the name of your plugin.
    // Note the value of the array key is normally a language string
    function onContentSearchAreas()
    {
        static $areas = array(
            'Mediawiki' => 'PLG_SEARCH_MEDIAWIKI_MEDIAWIKI'
        );
        return $areas;
    }

   /**
     * The function must return the following fields that are used in a common display
     * routine: href, title, section, created, text, browsernav
     *
     * @param string Target search string
     * @param string mathcing option, exact|any|all
     * @param string ordering option, newest|oldest|popular|alpha|category
     * @param mixed An array if the search it to be restricted to areas, [1] (wrap)
     * null if search all
     */
    function onContentSearch( $search_str, $mode='', $ordering='', $areas=null )
    {

        // NOTE: commenting out for now
        //$user = JFactory::getUser();
        //$groups   = implode(',', $user->getAuthorisedViewLevels());

        // NOTE: this function is default, can probably stay as is
        // If the array is not correct, return it:
        if (is_array( $areas )) {
            if (!array_intersect( $areas, array_keys( $this->onContentSearchAreas() ) )) {
                return array();
            }
        }

        // trim leading and trailing expression
        $search_str = trim($search_str);

        // NOTE: probably useful, leaving as is
        // Return Array when nothing was filled in.
        if ($search_str == '') {
            return array();
        }

        // Now retrieve the plugin parameters like this:
        //$nameofparameter = $this->params->get('nameofparameter', defaultsetting );
        $wiki_title = $this->params->get('wiki_title', 'Wiki');
        $wiki_baseurl = $this->params->get('wiki_baseurl', 'http://');
        $wiki_apiurl = $this->params->get('wiki_apiurl', $wiki_baseurl.'api.php');

        $limit = $this->params->get('search_limit', 20);

        // NOTE: normally srsort should support different options like:
        // create_timestamp_asc, create_timestamp_desc, incoming_links_asc, incoming_links_desc, just_match,
        // last_edit_asc, last_edit_desc, none, random, relevance, user_random
        // however it looks like our wiki only supports relevance,  therefore I'm leaving it out here

        // TODO: sorting could be implemented in the plugin itself after making the request

        // NOTE: also currently it is unclear if different search modes are supported

        // Set API parameters
        $params = array(
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $search_str,
            'srlimit' => $limit,
            'srnamespace' => 0,
            'srwhat' => 'text',
            //'srsort' => $srsort,
            'format' => 'json'
        );

        // Set API URL
        $api_url = $wiki_apiurl.'?'.http_build_query($params);

        // Make API request
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response
        $data = json_decode($response, true);

        // Check for errors
        if (isset($data['error'])) {
            return array();
        }

        // Assemble the result
        $res_arr = array();

        // Loop through search results
        foreach ($data['query']['search'] as $result) {
            // Get page title and URL
            $title = $result['title'];
            $url = $wiki_baseurl.'index.php?title='.$title;

            // Assemble result array
            $res_arr[] = (object) array(
                'href' => $url,
                'title' => $title,
                'section' => $wiki_title,
                'created' => '',
                'text' => $result['snippet'],
                'browsernav' => '1'
            );
        }

        return $res_arr;

/*
        // assemble a pseudo result for testing, adapted from tutorial
        // set variables
        $date_now = date("Y-m-d H:i:s");
        // (get plugin name) copied from below
        $section = JText::_( 'Nameofplugin' );

        $rows[] = (object) array(
            'href'        => "index.php?option=com_helloworld",
            'title'       => "Hello Search!",
            'section'     => $section,
            'created'     => $date_now,
            'text'        => "Hello this is the return text... Debug info:
Database host: ".$db_settings['host'].
"User for database authentication: ".$db_settings['user'].
"Database name: ".$db_settings['database'],
            'browsernav'  => '1'
        );
        return $rows;
*/
    }
}
