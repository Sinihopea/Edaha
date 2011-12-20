<?php

class manage_board_board_boardopts extends kxCmd {
  public function exec(kxEnv $environment){
    switch($this->request['do']) {
      case 'edit':
        $this->_edit();
        break;
      
      case 'post':
        $this->_update();
        break;
        
      default:
        //there's a problem
        die();
        break;
    }
    
    kxTemplate::output("manage/boardopts", $this->twigData);
  }
  
  private function _edit() {
    $board_options = kxEnv::Get('cache:boardopts:' . $this->request['board']);
    
    $this->twigData['board_options'] = $board_options[0];
    $this->twigData['sections'] = $this->db->select("sections")
                                           ->fields("sections")
                                           ->orderBy("section_order")
                                           ->execute()
                                           ->fetchAll();
    $this->twigData['filetypes'] = kxEnv::get('cache:attachments:filetypes');
  }
  
  private function _update() {
    // A few checks to ensure a valid submission
    kxForm::addRule('id', 'numeric')
          ->addRule('board', 'required')
          ->check();
    $board_exists = $this->db->select("boards")
                             ->condition("board_id", $this->request['id'])
                             ->countQuery()
                             ->execute()
                             ->fetchField();
    // Should return 1, otherwise something is very wrong
    if ($board_exists != 1) {
      die();
    }
    /*echo '<pre>';
    print_r($this->request);
    die();*/
    $board_fields = array(
              'board_desc'   => $this->request['title'],
              'board_locale' => $this->request['locale'],
              'board_type'   => (int) $this->request['type'],
              'board_upload_type' => (int) $this->request['upload_type'],
              'board_section' => (int) $this->request['board_section'],
              'board_order'   => (int) $this->request['order'],
              'board_header_image'   => $this->request['header_image'],
              'board_include_header' => $this->request['include_header'],
              'board_anonymous' => $this->request['anonymous'],
              // TODO: Add this to the template
              'board_default_style'   => 'edaha',
              'board_allowed_embeds'  => '',
              'board_max_upload_size' => (int) $this->request['max_upload_size'],
              'board_max_message_length' => (int) $this->request['max_message_length'],
              'board_max_pages'   => (int) $this->request['max_pages'],
              'board_max_age'     => (int) $this->request['max_age'],
              'board_mark_page'   => (int) $this->request['mark_page'],
              'board_max_replies' => (int) $this->request['max_replies']
             );
    
    // Is there a better way to do this? I hope to find one.
    $board_fields['board_locked'] = isset($this->request['locked']) ? 1 : 0;
    $board_fields['board_show_id'] = isset($this->request['show_id']) ? 1 : 0;
    $board_fields['board_compact_list'] = isset($this->request['compact_list']) ? 1 : 0;
    $board_fields['board_reporting'] = isset($this->request['reporting']) ? 1 : 0;
    $board_fields['board_captcha'] = isset($this->request['captcha']) ? 1 : 0;
    $board_fields['board_archiving'] = isset($this->request['archiving']) ? 1 : 0;
    $board_fields['board_catalog'] = isset($this->request['catalog']) ? 1 : 0;
    $board_fields['board_no_file'] = isset($this->request['no_file']) ? 1 : 0;
    $board_fields['board_redirect_to_thread'] = isset($this->request['redirect_to_thread']) ? 1 : 0;
    $board_fields['board_forced_anon'] = isset($this->request['forced_anon']) ? 1 : 0;
    $board_fields['board_trial'] = isset($this->request['trial']) ? 1 : 0;
    $board_fields['board_popular'] = isset($this->request['popular']) ? 1 : 0;
    
    $this->db->update("boards")
             ->fields($board_fields)
             ->condition('board_id', $this->request['id'])
             ->execute();
    
    // Clear previous filetype settings
    $this->db->delete("board_filetypes")
             ->condition("board_id", $this->request['id'])
             ->execute();
    
    // Add new filetypes
    
    foreach ($this->request['filetypes'] as $type) {
      $this->db->insert("board_filetypes")
               ->fields(array('board_id' => $this->request['id'], 'type_id' => $type))
               ->execute();
    }
                                    
    
    $this->twigData['boardredirect'] = true;
    $this->twigData['notice']['type'] = 'success';
    $this->twigData['notice']['message'] = _gettext('Board updated. Redirecting...');
    
    // Update the cache
    $this->recacheBoardOptions();
  }
  
  public function recacheBoardOptions() {
    // Get the requested board's options
    $recache_board_options = $this->db->select("boards")
                              ->fields("boards")
                              ->condition("board_name",$this->request['board'])
                              ->execute()
                              ->fetchAll();
    // Get its associated filetypes
    $recache_board_options[0]->board_filetypes = $this->db->select("board_filetypes")
                                               ->fields("board_filetypes", array('type_id'))
                                               ->condition("board_id", $recache_board_options[0]->board_id)
                                               ->execute()
                                               ->fetchCol();
    /*echo 'recache:<br><pre>';
    print_r($recache_board_options);
    echo '</pre>';
    die();*/
    // And cache them
    kxEnv::set('cache:boardopts:' . $this->request['board'], $recache_board_options);
  }
  
}