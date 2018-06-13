<?php 

class SeguroSaude {
  private static $initiated = false;

  public static function init()
  {
    if ( ! self::$initiated ) {
      self::initHooks();
    }
  }

  public static function plugin_activation()
  {
    if (!get_option('calc_ss_version')) {
      self::installTables();
      add_option('calc_ss_version', CALC_SS_VERSION);
    }
  }

  public static function plugin_deactivation()
  {
    global $wpdb;

    delete_option('calc_ss_version');

    // $planos = $wpdb->prefix . 'calc_ss_planos';
    // $modalidades = $wpdb->prefix . 'calc_ss_modalidades';
    // $categorias = $wpdb->prefix . 'calc_ss_categories';
    // $age_by_price = $wpdb->prefix . 'calc_ss_age_by_price';
    // $modalidades_has_categories = $wpdb->prefix . 'calc_ss_modalidades_has_categories';
    // $leads = $wpdb->prefix . 'calc_ss_leads';

    // $wpdb->query("DROP TABLE $planos");
    // $wpdb->query("DROP TABLE $modalidades");
    // $wpdb->query("DROP TABLE $categorias");
    // $wpdb->query("DROP TABLE $age_by_price");
    // $wpdb->query("DROP TABLE $modalidades_has_categories");
    // $wpdb->query("DROP TABLE $leads");
  }

  public static function adminPages()
  {
    add_menu_page(
      'Seguro Saúde',
      'Seguro Saúde',
      'manage_options', 
      'seguro-saude',
      function()
      {
        require('views/index.php');
      },
      'dashicons-heart',
      20
    );

    add_submenu_page(
      'seguro-saude',
      'Adicionar Plano',
      'Adicionar Plano',
      'manage_options', 
      'seguro-saude&action=planos',
      function()
      {
        require('views/index.php');
      }
    );

    add_submenu_page(
      'seguro-saude',
      'Modalidades',
      'Modalidades',
      'manage_options', 
      'seguro-saude&action=categories',
      function()
      {
        require('views/index.php');
      }
    );

    add_submenu_page(
      'seguro-saude',
      'Lista Interessados',
      'Lista Interessados',
      'read', 
      'seguro-saude-leads',
      function()
      {
        require('views/index.php');
      }
    );

    add_submenu_page(
      'seguro-saude',
      'Configurações',
      'Configurações',
      'manage_options', 
      'seguro-saude&action=config&step=1',
      function()
      {
        require('views/index.php');
      }
    );
  }

  public static function loadAdminScripts($hook)
  {
    if ($hook == "toplevel_page_seguro-saude" || $hook == "seguro-saude_page_seguro-saude-leads") {
      wp_register_style( 'ss_frontend_form_loader.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form_loader.css', array(), CALC_SS_VERSION);
      wp_register_style( 'ss_frontend_form_result.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form_result.css', array(), CALC_SS_VERSION);
      wp_register_style( 'ss_frontend_form.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form.css', array(), CALC_SS_VERSION);
      wp_register_style( 'ss_frontend_pricetable.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_pricetable.css', array(), CALC_SS_VERSION);

      wp_enqueue_style( 'ss_frontend_form_loader.css'); 
      wp_enqueue_style( 'ss_frontend_form_result.css'); 
      wp_enqueue_style( 'ss_frontend_form.css'); 
      wp_enqueue_style( 'ss_frontend_pricetable.css'); 

      wp_register_style( 'ss_form.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_form.css', array(), CALC_SS_VERSION);
      wp_enqueue_style( 'ss_form.css'); 

      wp_register_script( 'ss_form.js', plugin_dir_url( __FILE__ ) . 'static/js/ss_form.js', array('jquery'), CALC_SS_VERSION);
      wp_enqueue_script( 'ss_form.js');

      wp_register_script( 'ss_ajax.js', plugin_dir_url( __FILE__ ) . 'static/js/ss_ajax.js', array('jquery'), CALC_SS_VERSION, true);
      wp_enqueue_script( 'ss_ajax.js');
    }
  }

  public static function loadFrontendScripts()
  {
    wp_register_style( 'ss_frontend_form_loader.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form_loader.css', array(), CALC_SS_VERSION);
    wp_register_style( 'ss_frontend_form_result.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form_result.css', array(), CALC_SS_VERSION);
    wp_register_style( 'ss_frontend_form.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_form.css', array(), CALC_SS_VERSION);
    wp_register_style( 'ss_frontend_pricetable.css', plugin_dir_url( __FILE__ ) . 'static/css/ss_frontend_pricetable.css', array(), CALC_SS_VERSION);

    wp_enqueue_style( 'ss_frontend_form_loader.css'); 
    wp_enqueue_style( 'ss_frontend_form_result.css'); 
    wp_enqueue_style( 'ss_frontend_form.css'); 
    wp_enqueue_style( 'ss_frontend_pricetable.css'); 

    wp_register_script( 'ss_form_frontend.js', plugin_dir_url( __FILE__ ) . 'static/js/ss_form_frontend.js', array('jquery'), CALC_SS_VERSION);
    wp_enqueue_script( 'ss_form_frontend.js');
    wp_localize_script( 'ss_form_frontend.js', 'amb1_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
  }

  public static function processPlan()
  {
    /**********************************************
    *
    *
    *    Boostrap Plan Object
    *
    *
    **********************************************/

    parse_str($_POST['plan'], $parsed);

    $plansDB = array();
    $categories = array();
    $prices = array();
    $oldArray = array();

    $plansDB['plan_title'] = $parsed['plano_title'];

    foreach ($parsed as $key => $value) {
      $headers = explode("__", $key);
      $plan = array();
      $items = array();
      // Add categories to the Categories Array
      if (isset($headers[1]) && $headers[1] == "plan_category") {
        array_push($categories, $value);
      }

      // Do The Magic
      if (is_array($headers) && count($headers) == 5) {

        $slug = $headers[0];

        foreach ($parsed['plano_category'] as $title) {
          if ($slug == sanitize_title( $title )) {
            $plan['modalidade']['name'] = $title;
          }
        }

        if (isset($parsed['plano_modalidade__'.$slug])) {
          $plan['modalidade']['id'] = $parsed['plano_modalidade__'.$slug];
        }

        /**********************************************
        *
        * Check Ages From $parsed to the final object
        *
        **********************************************/

        if (isset($headers[1])) {
          $items['min'] = intval($headers[1]);  
        } else {
          $items['min'] = '';
        }

        if (isset($headers[2])) {
          $items['max'] = intval($headers[2]);
        } else {
          $items['max'] = '';
        }

        /**********************************************
        *
        *   Sort Prices
        *
        **********************************************/
        if (isset($headers[3])) {
          switch ($headers[3]) {
            case "coparticipacao":
            $items['price_cop'] = $value;
            break;
            case "participacao":
            $items['price_nocop'] = $value;
            break;
          }
          $plan['modalidade']['age_price'][] = $items;
        }

        /**********************************************
        *
        *
        *    MAGIC!!!!!!!
        *
        *
        **********************************************/
        if (array_key_exists($slug, $plansDB) ) {
          $masterKey = 0;
          $priceExists = array();
          $localPrices = $plan['modalidade']['age_price'][0];
          foreach ($plansDB[$slug]['modalidade']['age_price'] as $key => $plansDBPrice) {
            if ($plansDBPrice['min'] == $localPrices['min'] && $plansDBPrice['max'] == $localPrices['max']) {
              $agePrices = array_merge($plansDBPrice, $localPrices);

              if (!empty($plansDB[$slug]['modalidade']['true_age'])) {
                foreach ($plansDB[$slug]['modalidade']['true_age'] as $trueAge) {
                  if ($trueAge['min'] != $agePrices['min'] && $trueAge['max'] != $agePrices['max'] && $trueAge['price_cop'] != $agePrices['price_cop'] && $trueAge['price_nocop'] != $agePrices['price_nocop']) {
                   if (!in_array($agePrices, $plansDB[$slug]['modalidade']['true_age'])) {
                      $plansDB[$slug]['modalidade']['true_age'][] = $agePrices;      
                    }
                  }
                }
              } else {
                $plansDB[$slug]['modalidade']['true_age'][] = $agePrices;
              }
            } else {
              $plansDB[$slug]['modalidade']['age_price'][] = $localPrices;
            }
          }
        }
        else {
          $plansDB[$slug] = $plan;  
        }
      }
    }

    /**********************************************
    *
    *
    *    Check if has ages_id and then insert it
    *    Filter Control Key from
    *    Master Object
    *
    *
    **********************************************/
    if (isset($parsed['age_range_min_max__hidden'])) {
      $savedAges = $parsed['age_range_min_max__hidden'];
      $savedAgesCount = 0;

      foreach ($plansDB as $key => $plan) {
        if ($key != "plan_title" && $key != "plan_id") {
          unset($plansDB[$key]['modalidade']['age_price']);
          foreach ($plan['modalidade']['true_age'] as $ageKey => $ages) {
            $plansDB[$key]['modalidade']['true_age'][$ageKey]['id'] = explode(',', $savedAges[$ageKey])[$savedAgesCount];
          }
          $savedAgesCount++;
        }
      }
    }
    /**********************************************
    *
    *    Adding Categories
    *
    **********************************************/
    $count = 0;
    foreach ($plansDB as $key => $value) {
      if ($key != "plan_title") {
        $plansDB[$key]['categories'] = isset($categories[$count]) ? $categories[$count] : 0;
        $count++;
      }
    }
    /**********************************************
    *
    *
    *   Decide What to do Based on the Action
    *
    *
    **********************************************/
    if ($_POST['action'] == "new_plan") {
      $result = self::manageDB('insert', $plansDB, 'plan');
      echo json_encode($result);
    }

    if ($_POST['action'] == "edit_plan") {
      $plansDB['plan_id'] = $parsed['plan_id'];  
      $result = self::manageDB('edit', $plansDB, 'plan');
      echo json_encode($result);
    }

    wp_die();
  }

  public static function ajaxNewPlan()
  {

    if ($_POST['action'] == "new_plan_category") {
      parse_str($_POST['plan'], $parsed);
      
      $data = array(
        "name" => $parsed['ss-input-name'], 
        "slug" => sanitize_title($parsed['ss-input-name'],'', 'save')
      );

      if ( isset($parsed['item_id']) && !empty($parsed['item_id']) ) {
        $data['id'] = intval($parsed['item_id']);
        $result = self::manageDB('edit', $data, 'category');
      } else {
        $result = self::manageDB('insert', $data, 'category');
      }

      echo json_encode($result);

    };

    if ($_POST['action'] == "new_lead_status") {
      parse_str($_POST['plan'], $parsed);
      
      $data = array(
        "name" => $parsed['ss-input-name'], 
        "slug" => sanitize_title($parsed['ss-input-name'],'', 'save')
      );
      
      if ( isset($parsed['item_id']) && !empty($parsed['item_id']) ) {
        $data['id'] = intval($parsed['item_id']);
        $result = self::manageDB('edit', $data, 'status');
      } else {
        $result = self::manageDB('insert', $data, 'status');
      }

      echo json_encode($result);
    };

    if ($_POST['action'] == "delete_item") {
      $id = $_POST['id'];
      $section = $_POST['section'];
      $result = self::manageDB('delete', $id, $section);
      echo json_encode($result);
    };

    if ($_POST['action'] == "edit_item") {
      $id = intval($_POST['id']);
      $section = $_POST['section'];
      $result = self::manageDB('select', $id, $section);
      echo json_encode($result);
    };

    if ($_POST['action'] == "get_item") {
      $section = $_POST['section'];
      $result = self::manageDB('get', '', $section);
      echo json_encode($result);
    }

    wp_die();
  }

  public static function shortcodeForm($atts)
  {
    global $wpdb;

    if (array_key_exists('title', $atts)) {
      $formTitle = $atts['title'];
    }

    if (array_key_exists('button', $atts)) {
      $buttonText = $atts['button'];
    }

    if (array_key_exists('template', $atts)) {
      $slug = $atts['template'];
      $style = '';

      if ($styles = unserialize(get_option('ss-amb1-form-styles'))) {
        foreach ($styles as $key => $item) {
          if ($item['slug'] == $slug) {
            $style = $item;
          } 
        }
      }

      $css = $style['formStyle'] . $style['resultStyle'];

      wp_register_style( 'custom-form-styles', false );
      wp_enqueue_style( 'custom-form-styles' );
      wp_add_inline_style( 'custom-form-styles', $css );
    }

    $categories = self::getCategory();
    $ages = self::getAges();
    $plans = self::getPlans();

    /**********************************************
    *    HTML INPUTS
    **********************************************/
    $htmlName = '<div class="ss-amb1-field__wrapper"><label class="ss-amb1-field__label">Nome:</label><input type="text" value="" name="ss-amb1-name"></div>';
    $htmlEmail = '<div class="ss-amb1-field__wrapper"><label class="ss-amb1-field__label">Email:</label><input type="email" value="" name="ss-amb1-email"></div>';
    $htmlPhone = '<div class="ss-amb1-field__wrapper"><label class="ss-amb1-field__label">Telefone:</label><input type="text" value="" name="ss-amb1-phone"></div>';
    /**********************************************
    *    Select Adesão
    **********************************************/
    $htmlOption = '';
    foreach ($categories['data'] as $category) {
      $htmlOption .= '<option value="'.$category->id.'">'.$category->name.'</option>';
    }
    $htmlSelect = '<div class="ss-amb1-field__wrapper"><label class="ss-amb1-field__label">Adesão:</label><select name="ss-amb1-modalidades-categoria" class="ss-amb1--select">'.$htmlOption.'</select></div>';
    /**********************************************
    *    Select Planos
    **********************************************/
    $htmlPlans = '';
    foreach ($plans['data'] as $plan) {
      $htmlPlans .= '<option value="'.$plan->id.'">'.$plan->name.'</option>';
    }
    $htmlPlans = '<div class="ss-amb1-field__wrapper ss-amb1-field__wrapper--block"><label class="ss-amb1-field__label">Plano de Saúde:</label><select name="ss-amb1-modalidades-plano" class="ss-amb1--select"><option selected>Todos</option>'.$htmlPlans.'</select></div>';
    /**********************************************
    *    List of Ages
    **********************************************/
    if ($ages['data']) {
      $htmlAges = '<div class="ss-amb1--ages__wrapper">';  
      foreach ($ages['data'] as $age) {
        $htmlAges .= '<label class="ss-amb1-field__wrapper"><label class="ss-amb1-field__label">'.$age->age_min.' a '.$age->age_max.'</label><input type="number" value="" class="ss-amb1--field__input" name="ss-amb1-age['.$age->age_min.'__'.$age->age_max.']"> </label>';
      }
      $htmlAges .= '</div>';
    }

    $submit = '<div class="ss-amb1-field__wrapper"><input class="button-primary" type="submit" name="Example" value="'.$buttonText.'" /></div>';

    $html = '<div class="ss-amb1--wrapper"><form name="ss-amb1-form" action="POST" class="ss-amb1--form__wrapper"><legend class="ss-amb1-legend">'.$formTitle.'</legend>'.$htmlName.$htmlEmail.$htmlPhone.$htmlSelect.$htmlPlans.$htmlAges.$submit.'</form></div>';

    return $html;
  }

  public static function shortcodePriceTable($atts) 
  {
    if (!$atts['plano']) {
      echo '<div style="clear:both; display:block;" class="class="ss-results--plan">Não foi possível carregar esta tabela de preço</div>';
    } else {

      $htmlID = 'pricetable-'.substr(explode('.',microtime())[1], 0, 4);

      if (array_key_exists('color', $atts) || array_key_exists('width', $atts) || array_key_exists('fontsize', $atts)) {
        $rootID = '#'.$htmlID;
        $css = '';

        if (array_key_exists('color', $atts)) {
          $rootColor = $atts['color'];
          $titleColor = '#'.dechex(hexdec(str_replace("#", "", $rootColor)) + 658187);
          $css .= $rootID .' .title{background-color: '.$titleColor.';}'
          .$rootID .' .content,'
          .$rootID .' .pt-footer { background: '.$rootColor.';}';
        }

        if (array_key_exists('width', $atts)) {
          $css .= $rootID.'{width: '.$atts['width'].';}';
        }

        if (array_key_exists('fontsize', $atts)) {
          $css .= $rootID.'{font-size: '.$atts['fontsize'].';}';
        }

        wp_register_style( 'shortcode-styles', false );
        wp_enqueue_style( 'shortcode-styles' );
        wp_add_inline_style( 'shortcode-styles', $css );
      }

      $plan = self::filterPlan(self::selectPlan($atts['plano']));  
      $titulo = '<h2 class="title">'.$plan['name'].'</h2>';
      $modalidades = '<div class="content">';
      foreach ($plan['modalidade'] as $key => $modalidade) {
        $tbody = '<p class="hint">'.$modalidade['value'].'</p>';
        $agePrices = '<div class="features"><table class="ss-results--plan-prices" border="0" cellpadding="0" cellspacing="0">
        <thead>
        <tr>
        <th>Faixa Etária</th>
        <th>Coparticipação</th>
        <th>Sem Coparticipação</th>
        </tr>
        </thead><tbody>';
        foreach ($plan['ages'] as $ageKey => $age) {
          $prices = array_shift($plan['prices']);
          $line = '<tr>
          <td>'.$age['min'] .' - ' .$age['max'].'</td>
          <td>R$ '.$prices['price_cop'].'</td>
          <td>R$ '.$prices['price_nocop'].'</td>
          </tr>';
          $agePrices .= $line;
        }
        $modalidades .= $tbody . $agePrices . '</tbody></table></div>';
      }

      $sh = '<div id="'.$htmlID.'" class="ss-results--plan">'.$titulo.$modalidades.'</div><div class="pt-footer">&nbsp;</div></div>';
      
      return $sh;
    }
  }

  public static function manageDB($action, $data, $section)
  {
    if ($section == "plan") {
      switch ($action) {
        case "insert":
        return self::addPlan($action, $data, $section);
        break;
        case "edit":
        return self::editPlan($data);
        break;
        case "select":
        return self::selectPlan($action, $data, $section);
        break;
        case "delete":
        return self::deletePlan($action, $data, $section);
        break;
        default:
        return $result = array("status" => false, "msg" => "Faça uma escolha");
        break;
      }
    }

    if ($section == "category"){
      switch ($action) {
        case "insert":
        return self::addCategory($action, $data, $section);
        break;
        case "delete":
        return self::deleteCategory($action, $data, $section);
        break;
        case "get":
        return self::getCategory();
        break;
        case "select":
        return self::selectCategory($data);
        break;
        case "edit":
        return self::editCategory($data);
        break;
        default:
        return $result = array("status" => false, "msg" => "Faça uma escolha");
        break;
      }
    }

    if ($section == "status"){
      switch ($action) {
        case "insert":
        return self::addStatus($action, $data, $section);
        break;
        case "delete":
        return self::deleteStatus($data);
        break;
        case "get":
        return self::getStatus();
        break;
        case "select":
        return self::selectStatus($data);
        break;
        case "edit":
        return self::editStatus($data);
        break;
        default:
        return $result = array("status" => false, "msg" => "Faça uma escolha");
        break;
      }
    }

    if ($section == "lead"){
      switch ($action) {
        case "insert":
        return self::addLead($data);
        break;
        case "export":
        return self::exportLeads();
        break;
        case "select":
        return self::selectLeadsPlan($data);
        break;
        case "edit":
        return self::editLead($data);
        break;
        default:
        return $result = array("status" => false, "msg" => "Faça uma escolha");
        break;
      }
    }

    if ($section == "form_style"){
      switch ($action) {
        case "delete":
        $styles = array();
        if ($oldStyles = unserialize(get_option('ss-amb1-form-styles'))) {
          foreach ($oldStyles as $key => $item) {
            if ($item['slug'] == $data) {
              unset($oldStyles[$key]);
            } else {
              $styles[] = $item;
            }
          }
        }

        update_option( 'ss-amb1-form-styles', serialize($styles) );

        return $result = array("status" => true, "msg" => "Faça uma escolha");

        break;
        default:
        return $result = array("status" => false, "msg" => "Faça uma escolha");
        break;
      }
    }
  }

  public static function processForm()
  {
    parse_str($_POST['form'], $parsed);
    $data = array(
      "name" => $parsed['ss-amb1-name'],
      "email" => $parsed['ss-amb1-email'],
      "phone" => $parsed['ss-amb1-phone'],
      "adesao" => $parsed['ss-amb1-modalidades-categoria'],
      "ages" => $parsed['ss-amb1-age'],
      "responsible" => self::randomUser()
    );
    /**********************************************
    *
    *    INSERT LEAD AND CREATE EMAILS
    *
    **********************************************/
    $result = self::manageDB('insert', $data, 'lead');

    if (isset($result['status'])) {
      $subject = 'SS Plugin - Um novo lead se cadastrou no site';
      $message = '<p>Um novo usuário se cadastrou no site. Faça o login no sistema para ver todos os detalhes.</p> 
      <p>Abaixo o nome e o email: <br>
      <b>Nome: </b>'.$parsed['ss-amb1-name'].' <br>
      <b>Email: </b>'.$parsed['ss-amb1-email'].' <br>
      </p><hr>
      <p>
      <a href="'.admin_url().'">Acesse o site e veja os detalhes</a>
      </p>';
    } else {
      $subject = 'Algum usuário tentou se cadastrar mas não conseguiu';
      $message = '<p>O usuário'.$parsed['ss-amb1-name'].' tentou se cadastrar no site mas não conseguimos incluir no banco de dados.</p> 
      <p>Abaixo está os dados do mesmo:
      <b>nome: </b>'.$parsed['ss-amb1-name'].' <br>
      <b>email: </b>'.$parsed['ss-amb1-email'].' <br>
      <b>telefone: </b>'.$parsed['ss-amb1-phone'].' <br>
      <b>adesao: </b>'.$parsed['ss-amb1-modalidades-categoria'].' <br>
      <b>idades: </b>'.unserialize($parsed['ss-amb1-age']).'</p><hr>
      <p>
      <a href="'.admin_url().'">Acesse o site e veja os detalhes</a>
      </p>';
    }
    /**********************************************
    *
    *    SEND EMAILS
    *
    **********************************************/
    if (get_option('ss-amb1-sys-emails')) {
      $to = get_option('ss-amb1-sys-emails');
    } else {
      $to = get_bloginfo('admin_email');
    }
    wp_mail( $to, $subject, $message);
    /**********************************************
    *
    *    DO THE CALCULATIONS
    *
    **********************************************/
    $filtered = array();
    $hasChild = true;
    $hasChild2 = false;
    $ages = array(
      "min" => array(),
      "max" => array(),
    );
    // Set Min and Max Ages for SQL
    foreach ($parsed['ss-amb1-age'] as $age => $qtd) {
      if (intval($qtd) > 0 ) {
        $age = explode("__", $age);
        if (!in_array($age[0], $ages['min'])) {
          array_push($ages['min'], $age[0]);
        }
        if (!in_array($age[1], $ages['max'])) {
          array_push($ages['max'], $age[1]);
        }
      }
    }
    // Prepare the SQL
    $data = array(
      "ages" => $ages,
      "category" => $parsed['ss-amb1-modalidades-categoria'],
      "plan_id" => intval($parsed['ss-amb1-modalidades-plano']),
      "qtd" => $parsed['ss-amb1-age']
    );
    // THE WHOLE MAGIC GOES HERE
    if ($entries = self::manageDB('select', $data, 'lead')) {
      foreach ($entries as $line) {
        if (empty($filtered)) {
          array_push($filtered, self::filterPlan2(array($line), $parsed));
        } else {
          foreach ($filtered as $key => $item) {
            if (in_array($line->name, $item)) {
              $hasChild = false;
              $new = self::filterPlan2(array($line), $parsed);
              foreach ($filtered[$key]['modalidade'] as $modalKey => $modalidade) {
                if (in_array($new['modalidade'][0]['value'], $modalidade)) {
                  $hasChild2 = false;
                  $filtered[$key]['modalidade'][$modalKey]['ages'] = array_merge($filtered[$key]['modalidade'][$modalKey]['ages'], $new['modalidade'][0]['ages']);
                  $filtered[$key]['modalidade'][$modalKey]['prices'] = array_merge($filtered[$key]['modalidade'][$modalKey]['prices'], $new['modalidade'][0]['prices']);
                } else {
                  $hasChild2 = true;
                }
              }
            } else {
              $hasChild = true;
            }
          }
          if ($hasChild) {
            array_push($filtered, self::filterPlan2(array($line), $parsed));
          }
          if ($hasChild2) {
            $new = self::filterPlan2(array($line), $parsed);
            array_push($filtered[$key]['modalidade'], $new['modalidade'][0]);
          }
        }
      }
    }
    if (empty($filtered)) {
      $result = array("status" => false, "msg" => "Não conseguimos encontrar um plano com esta configuração."); 
    } else {
      $result = array("status" => true, "msg" => (get_option('ss-amb1-feform-success')) ? get_option('ss-amb1-feform-success') : '', "data" => $filtered);
    }
    echo json_encode($result);
    wp_die();
  }

  public static function configForm($hook)
  {
    $action = $_POST['action'];
    $redirect = $_POST['redirect'];
    switch ($action) {
      case "config_step1":
      if (isset($_POST['config-msg-leads'])) {
        $msgLeads = trim($_POST['config-msg-leads']);
        update_option('ss-amb1-msg-leads', $msgLeads);
      }
      if (isset($_POST['config-feform-success'])) {
        update_option('ss-amb1-feform-success', $_POST['config-feform-success']);
      }
      if (isset($_POST['config-email'])) {
        $configEmail = explode("\n", trim($_POST['config-email']));
        $emails = join(',', $configEmail);
        update_option('ss-amb1-sys-emails', $emails); 
      }

      break;
    }
    wp_redirect(admin_url($redirect));
    die();
  }

  public static function mailCharset()
  {
    return 'UTF-8';
  }

  public static function mailContentType()
  {
    return 'text/html';
  }

  public static function leadsExport()
  {
    date_default_timezone_set('America/Sao_Paulo');
    $data = self::manageDB('export', false, 'lead');
    $file_path = SEGUROSAUDE__PLUGIN_DIR . "static/leads.csv";
    if (fopen($file_path, 'a+')) {
      unlink($file_path);
    }
    $csv = fopen($file_path, 'a+');
    $csvData = fgetcsv($csv, 10000, ",");
    $is_header = explode(",", $csvData[0]);
    if ($is_header[0] != "Nome") {
      $header = array("Nome","Email","Telefone","Modalidade","Data","Faixa Etária", "Idade", "Modalidade");
      fputcsv($csv, $header);
    } 
    $result = array();
    foreach ($data as $item) {
      $data = array();
      foreach ($item as $key => $line) {
        if ($key == "created_at") {
          $data["data"] = date('d/m/Y H:m:s', strtotime($line));
        } else if($key == "ages_selected"){
          $ages = unserialize($line);
          $keyage = array_keys($ages);
          $data["faixa_etaria"] = $keyage[0];
          $data["idade"] = $ages[$keyage[0]];
        } else {
          $data[$key] = $line;
        }
      }
      fputcsv($csv, $data);
    }
    fclose($csv);

    $file_name = SEGUROSAUDE__PLUGIN_DIR . "static/leads.csv";

    header('Pragma: public');   // required
    header('Expires: 0');   // no cache
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($file_name)).' GMT');
    header('Cache-Control: private',false);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="leads.csv"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize($file_name));  // provide file size
    header('Connection: close');
    readfile($file_name);   // push it out
    wp_die();
  }

  public static function leadsEdit()
  {
    $data = array(
      "status" => intval($_POST['lead-status']),
      "obs" => $_POST['lead-obs'],
      "id" => intval($_POST['item_id'])
    );
    $results = self::ManageDb('edit', $data, 'lead');
    if ($results['status']) {
      wp_redirect(admin_url("admin.php?page=seguro-saude-leads&status=success"));
    } else {
      wp_redirect(admin_url("admin.php?page=seguro-saude-leads&status=fail"));
    }
    die();
  }

  public static function planSelect()
  {
    if (isset($_GET['data'])) {
      $data = $_GET['data'];
      $results = self::ManageDb('select', $data, 'plan');
      if ($results['status']) {
        wp_redirect(admin_url("admin.php?page=seguro-saude-leads&status=success"));
      } else {
        wp_redirect(admin_url("admin.php?page=seguro-saude-leads&status=fail"));
      }
      die();
    }
  }

  public static function saveFormStyles(){
    $title = $_POST['style_title'];
    $formStyle = $_POST['formFieldsStyles'];
    $resultStyle = $_POST['formResultStyles'];
    $oldSlug = $_POST['old_slug'];
    $styles = array();

    $newStyles = array(
      "name" => $title,
      "slug" => sanitize_title( $title ),
      "formStyle" => $formStyle,
      "resultStyle" => $resultStyle
    );

    if ($oldStyles = unserialize(get_option('ss-amb1-form-styles'))) {
      foreach ($oldStyles as $key => $item) {
        if ($item['slug'] == $oldSlug) {
          $item = $newStyles;
        } 
        $styles[] = $item;
      }
    } 

    if (empty($oldSlug)) {
      array_push($styles, $newStyles);
    }

    update_option( 'ss-amb1-form-styles', serialize($styles) );
    wp_redirect( admin_url('admin.php?page=seguro-saude&action=config&step=3') );
  }

  /**********************************************
  *
  *
  *    PRIVATE METHODS
  *
  *
  **********************************************/


  /**********************************************
  *
  *    Database Model a like Methods
  *
  **********************************************/
  
  private static function selectLeadsPlan($data)
  {
    global $wpdb;
    $result = array();
    $planos = $wpdb->prefix . 'calc_ss_planos';
    $modalidades = $wpdb->prefix . 'calc_ss_modalidades';
    $categorias = $wpdb->prefix . 'calc_ss_categories';
    $age_by_price = $wpdb->prefix . 'calc_ss_age_by_price';
    $modalidades_has_categories = $wpdb->prefix . 'calc_ss_modalidades_has_categories';
    
    $ages_min = min($data["ages"]['min']) . ' AND ' . max($data["ages"]['min']);
    $ages_max = min($data["ages"]['max']) . ' AND ' . max($data["ages"]['max']);
    $categorias_id = $data["category"];

    if ($data['plan_id'] > 0) {
      $plan_id = $data['plan_id'];
      $where = "WHERE $planos.id = $plan_id AND age_min BETWEEN $ages_min AND age_max BETWEEN $ages_max AND categorias_id = $categorias_id";
    } else {
      $where = "WHERE age_min BETWEEN $ages_min AND age_max BETWEEN $ages_max AND categorias_id = $categorias_id";
    }

    

    $sql = "SELECT DISTINCT
    $planos.id,
    $planos.name AS 'name',
    $planos.slug,
    $modalidades.id AS 'modalidade_id',
    $modalidades.name AS 'modalidade',
    $age_by_price.age_min,
    $age_by_price.age_max,
    $age_by_price.price_cop,
    $age_by_price.price_nocop,
    $categorias.name as 'categoria_name'

    FROM $age_by_price
    INNER JOIN $modalidades_has_categories ON $age_by_price.modalidades_id = $modalidades_has_categories.modalidades_id
    INNER JOIN $modalidades ON $age_by_price.modalidades_id = $modalidades.id
    INNER JOIN $categorias ON $modalidades_has_categories.categorias_id = $categorias.id
    INNER JOIN $planos ON $modalidades.planos_id = $planos.id

    $where ORDER BY modalidade ASC"; 
    
    if ( $results = $wpdb->get_results($sql) ) {
      return $results;
    } else {
      return false;
    }
  }

  private static function selectStatus($data)
  {
    global $wpdb;
    $result = array();
    $planos = $wpdb->prefix . 'calc_ss_status';
    $sql = "SELECT * FROM $planos WHERE id = $data";
    if ( $line = $wpdb->get_row($sql) ) {
      return $line;
    } else{
      return false;
    }
  }

  private static function selectCategory($data)
  {
    global $wpdb;
    $result = array();
    $planos = $wpdb->prefix . 'calc_ss_categories';
    $sql = "SELECT * FROM $planos WHERE id = $data";
    if ( $line = $wpdb->get_row($sql) ) {
      return $line;
    } else{
      return false;
    }
  }

  private static function selectPlan($data)
  {
    global $wpdb;
    $result = array();
    $table_name_planos = $wpdb->prefix . 'calc_ss_planos';
    $table_name_modalidades = $wpdb->prefix . 'calc_ss_modalidades';
    $table_name_age_by_price = $wpdb->prefix . 'calc_ss_age_by_price';
    $table_name_modalidades_has_categories = $wpdb->prefix . 'calc_ss_modalidades_has_categories';

    if (intval($data) != 0) {
      $where = "WHERE $table_name_planos.id = $data";
    } else {
      $where = "WHERE $table_name_planos.slug = '$data'";
    }

    $sql = "SELECT
    $table_name_planos.id as 'id',
    $table_name_planos.name as 'name',
    $table_name_planos.slug,
    $table_name_modalidades.id as 'modalidade_id',
    $table_name_modalidades.name as 'modalidade',
    $table_name_age_by_price.age_min,
    $table_name_age_by_price.age_max,
    $table_name_age_by_price.price_cop,
    $table_name_age_by_price.price_nocop,
    $table_name_modalidades_has_categories.categorias_id as 'categoria_id'

    FROM $table_name_planos

    INNER JOIN $table_name_modalidades ON $table_name_planos.id = $table_name_modalidades.planos_id
    INNER JOIN $table_name_age_by_price ON $table_name_age_by_price.modalidades_id = $table_name_modalidades.id
    INNER JOIN $table_name_modalidades_has_categories ON $table_name_modalidades_has_categories.modalidades_id = $table_name_modalidades.id

    $where";

    if ($line = $wpdb->get_results($sql)) {
      return $line;
    } else {
      return false;
    }
  }

  private static function getAges()
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_age_by_price';
    if ($row = $wpdb->get_results("SELECT DISTINCT age_min, age_max FROM $table")) {
      return $result = array("status" => true, "msg" => "Item pesquisado com sucesso", "data" => $row);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function getCategory()
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_categories';
    if ($row = $wpdb->get_results("SELECT * FROM $table")) {
      return $result = array("status" => true, "msg" => "Item pesquisado com sucesso", "data" => $row);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function getStatus()
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_status';
    if ($row = $wpdb->get_results("SELECT * FROM $table")) {
      return $result = array("status" => true, "msg" => "Item pesquisado com sucesso", "data" => $row);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function getPlans()
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_planos';
    if ($row = $wpdb->get_results("SELECT * FROM $table")) {
      return $result = array("status" => true, "msg" => "Item pesquisado com sucesso", "data" => $row);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function addCategory($action, $data, $section)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_categories';
    $fields = array(
      'name' => $data['name'],
      'slug' => $data['slug']
    );
    $types = array('%s','%s');
    if ($wpdb->insert($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item incluído com sucesso",  "id" => $wpdb->insert_id, "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function addLead($data)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_leads';
    $fields = array(
      "name" => $data["name"],
      "email" => $data["email"],
      "telefone" => $data["phone"],
      "categorias_id" => $data["adesao"],
      "ages_selected" => serialize($data["ages"]),
      "responsible" => $data["responsible"]
    );
    $types = array('%s', '%s', '%s', '%d', '%s', '%d');
    if ($wpdb->insert($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item incluído com sucesso",  "id" => $wpdb->insert_id, "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function addPlan($action, $data, $section)
  {
    global $wpdb;
    $result = array();
    /**********************************************
    *
    *    INSERT PLAN
    *
    **********************************************/
    $table = $wpdb->prefix . 'calc_ss_planos';
    $totalModalidades = count($data) - 1; // all first level keys from array data, minus the "plain_title" one
    $fields = array(
      'name' => $data['plan_title'],
      'slug' => sanitize_title($data['plan_title']),
      'modalidades' => $totalModalidades,  
    );
    $types = array('%s', '%s', '%d');
    $wpdb->insert($table, $fields, $types);
    if ($planID = $wpdb->insert_id) {
      /**********************************************
      *
      *    INSERT MODALIDADES
      *
      **********************************************/
      foreach ($data as $key => $item) {
        if ($key != "plan_title") {
          $table = $wpdb->prefix . 'calc_ss_modalidades';
          $modalidade = str_replace("_", " ", $item['modalidade']['name']);
          $fields = array(
            'name' => $modalidade,  
            'planos_id' => $planID
          );
          $types = array('%s','%d');
          $wpdb->insert($table, $fields, $types);

          if ($modalidadeID = $wpdb->insert_id) {
            /**********************************************
            *
            *    INSERT MODALIDADES CATEGORIES
            *
            **********************************************/
            if ($item['categories']) {
              foreach ($item['categories'] as $category) {
                $table = $wpdb->prefix . 'calc_ss_modalidades_has_categories';
                $fields = array(
                  'modalidades_id' => $modalidadeID,
                  'categorias_id' => intval($category)
                );
                $types = array('%d','%d');
                $wpdb->insert($table, $fields, $types);
              }
            }
            /**********************************************
            *
            *    INSERT PRICE AND AGES
            *
            **********************************************/
            if ($item['modalidade']['true_age']) {
              foreach ($item['modalidade']['true_age'] as $priceAge) {
                if (array_key_exists('price_cop', $priceAge) && array_key_exists('price_nocop', $priceAge)) {
                  $table = $wpdb->prefix . 'calc_ss_age_by_price';
                  $fields = array(
                    'age_min' => $priceAge['min'],
                    'age_max' => $priceAge['max'],
                    'price_cop' => $priceAge['price_cop'],
                    'price_nocop' => $priceAge['price_nocop'],
                    'modalidades_id' => $modalidadeID
                  );
                  $types = array('%d','%d','%d','%d');
                  $wpdb->insert($table, $fields, $types);
                }
              }
            }
          } else {
            return $result = array("status" => false, "msg" => "House um erro ao adicionar o plano 2");
          }
        }
      }
      return $result = array("status" => true, "msg" => "Mensagem inclusa em sucesso.");
    } else {
      return $result = array("status" => false, "msg" => "House um erro ao adicionar o plano 1");
    }
  }

  private static function addStatus($action, $data, $section)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_status';
    $fields = array(
      'name' => $data['name'],
      'slug' => $data['slug']
    );
    $types = array('%s','%s');
    if ($wpdb->insert($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item incluído com sucesso",  "id" => $wpdb->insert_id, "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar o Status");
    }
  }

  private static function editPlan($data)
  {
    global $wpdb;
    // echo '<pre>';
    // var_dump($data);
    // echo '</pre>';
    // exit();
    /**********************************************
    *
    *    UPDATE PLAN
    *
    **********************************************/
    $table = $wpdb->prefix . 'calc_ss_planos';
    $totalModalidades = count($data) - 1; // all first level keys from array data, minus the "plain_title" one
    $fields = array(
      'name' => $data['plan_title'],
      'slug' => sanitize_title( $data['plan_title'] ),
      'modalidades' => $totalModalidades,  
      'updated_at' => current_time( 'mysql' )
    );
    $types = array('%s','%s','%d', '%s');
    if ( $wpdb->update($table, $fields, array('ID' => intval($data['plan_id'])), $types, array('%d')) ) {
      /**********************************************
      *
      *    UPDATE MODALIDADES
      *
      **********************************************/
      foreach ($data as $key => $item) {
        if ($key != "plan_title" && $key != "plan_id") {
          $table = $wpdb->prefix . 'calc_ss_modalidades';
          $modalidade = $item['modalidade']['name'];
          $fields = array(
            'name' => $modalidade,  
            'planos_id' => $data['plan_id'],
            'updated_at' => current_time( 'mysql' )
          );
          $types = array('%s','%d');
          if (isset($item['modalidade']['id'])) {
            $modalidadeID = $item['modalidade']['id'];
            $sqlOrder = $wpdb->update($table, $fields, array('ID' => $item['modalidade']['id']), $types, array('%d'));
          } else {
            $wpdb->insert($table, $fields, $types);
            $modalidadeID = $wpdb->insert_id;
          }

          if ($modalidadeID) {
            /**********************************************
            *
            *    UPDATE MODALIDADES CATEGORIES
            *
            **********************************************/
            if (isset($item['categories'])) {
              $table = $wpdb->prefix . 'calc_ss_modalidades_has_categories';
              // delete all categories of this Modalidade
              $wpdb->delete($table, array('modalidades_id' => $modalidadeID), array('%d'));
              // if delete is successfull, add all new categories
              if (isset($item['categories'])) {
                foreach ($item['categories'] as $category) {
                  $fields = array(
                    'modalidades_id' => intval($modalidadeID),
                    'categorias_id' => intval($category)
                  );
                  $types = array('%d','%d');
                  $wpdb->insert($table, $fields, $types);
                }
              }
            }
            /**********************************************
            *
            *    UPDATE PRICE AND AGES
            *
            **********************************************/
            if (isset($item['modalidade']['true_age'])) {
              foreach ($item['modalidade']['true_age'] as $priceAge) {
                if (array_key_exists('price_cop', $priceAge) && array_key_exists('price_nocop', $priceAge)) {
                  $table = $wpdb->prefix . 'calc_ss_age_by_price';
                  $fields = array(
                    'age_min' => $priceAge['min'],
                    'age_max' => $priceAge['max'],
                    'price_cop' => $priceAge['price_cop'],
                    'price_nocop' => $priceAge['price_nocop'],
                    'modalidades_id' => $modalidadeID
                  );
                  $types = array('%d','%d','%d','%d');
                  // Check if row exists
                  if (!empty($priceAge['id'])) {
                    $fields['updated_at'] = current_time( 'mysql' );
                    $wpdb->update($table, $fields, array('ID' => $priceAge['id']), $types, array('%d'));  
                  } else {
                    $wpdb->insert($table, $fields, $types);  
                  }
                }
              }
            }
          } else {
            return $result = array("status" => false, "msg" => "House um erro ao adicionar o plano 2");
          }
        }
      }
      return $result = array("status" => true, "msg" => "Plano atualizado com sucesso.");
    } else {
      return $result = array("status" => false, "msg" => "House um erro ao adicionar o plano 1");
    }
  }

  private static function editCategory($data)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_categories';
    $fields = array(
      'name' => $data['name'],
      'slug' => $data['slug']
    );
    $types = array('%s','%s');
    if ($wpdb->update($table, $fields, array("ID" => $data['id']), $types, array('%d'))) {
      return $result = array("status" => true, "msg" => "Item atualizado com sucesos com sucesso", "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar o Status");
    }
  }

  private static function editStatus($data)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_status';
    $fields = array(
      'name' => $data['name'],
      'slug' => $data['slug']
    );
    $types = array('%s','%s');
    if ($wpdb->update($table, $fields, array("ID" => $data['id']), $types, array('%d'))) {
      return $result = array("status" => true, "msg" => "Item atualizado com sucesos com sucesso", "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar o Status");
    }
  }

  private static function editLead($data)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_leads';
    $fields = array(
      "status_id" => $data["status"],
      "obs" => $data["obs"],
    );
    $types = array('%d', '%s');
    if ($wpdb->update($table, $fields, array('id' => $data['id']), $types, array('%d'))) {
      return $result = array("status" => true, "msg" => "Item editado com sucesso", "data" => $fields);
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao editar este lead");
    }
  }

  private static function deleteCategory($action, $data, $section)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_categories';
    $fields = array(
      'id' => $data
    );
    $types = array('%d');
    if ($wpdb->delete($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item deletado com sucesso");
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function deleteStatus($data)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_status';
    $fields = array(
      'id' => $data
    );
    $types = array('%d');
    if ($wpdb->delete($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item deletado com sucesso");
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao adicionar os dados finais 3");
    }
  }

  private static function deletePlan($action, $data, $section)
  {
    global $wpdb;
    $result = array();
    $table = $wpdb->prefix . 'calc_ss_planos';
    $fields = array(
      'id' => $data
    );
    $types = array('%d');
    if ($wpdb->delete($table, $fields, $types)) {
      return $result = array("status" => true, "msg" => "Item deletado com sucesso");
    } else {
      return $result = array("status" => false, "msg" => "Houve um erro ao deletar os dados");
    }
  }

  private static function exportLeads()
  {
    global $wpdb;
    $leads = $wpdb->prefix . 'calc_ss_leads';
    $categories = $wpdb->prefix . 'calc_ss_categories';
    $sql = "SELECT
    $leads.id,
    $leads.name,
    $leads.email,
    $leads.telefone,
    $leads.created_at,
    $leads.ages_selected,
    $categories.name as 'modalidade'
    FROM $leads
    INNER JOIN $categories ON $leads.categorias_id = $categories.id";
    $results = $wpdb->get_results($sql);
    return $results;
  }

  /**********************************************
  *
  *    INITIALIZERS
  *
  **********************************************/

  private static function initHooks()
  {
    self::$initiated = true;
    /**********************************************
    *
    *    Admin Hooks
    *
    **********************************************/
    add_action('admin_menu', array('SeguroSaude', 'adminPages') );
    add_action('admin_enqueue_scripts', array('SeguroSaude', 'loadAdminScripts') );
    add_action('wp_ajax_new_plan', array('SeguroSaude', 'processPlan') );
    add_action('wp_ajax_edit_plan', array('SeguroSaude', 'processPlan') );
    add_action('wp_ajax_new_plan_category', array('SeguroSaude', 'ajaxNewPlan') );
    add_action('wp_ajax_new_lead_status', array('SeguroSaude', 'ajaxNewPlan') );
    add_action('wp_ajax_delete_item', array('SeguroSaude', 'ajaxNewPlan') );
    add_action('wp_ajax_edit_item', array('SeguroSaude', 'ajaxNewPlan') );
    add_action('wp_ajax_get_item', array('SeguroSaude', 'ajaxNewPlan') );
    add_action('admin_post_config_step1', array('SeguroSaude', 'configForm') );
    add_action('admin_post_export_leads', array('SeguroSaude', 'leadsExport') );
    add_action('admin_post_edit_leads', array('SeguroSaude', 'leadsEdit') );
    add_action('admin_post_select_plan', array('SeguroSaude', 'planSelect') );
    add_action('admin_post_save_form_styles', array('SeguroSaude', 'saveFormStyles') );
    add_shortcode('seguro-saude', array('SeguroSaude', 'shortcodeForm'));
    add_shortcode('plano-valores', array('SeguroSaude', 'shortcodePriceTable'));
    /**********************************************
    *
    *    Filters
    *
    **********************************************/
    add_filter( 'wp_mail_charset', array('SeguroSaude', 'mailCharset') );
    add_filter( 'wp_mail_content_type', array('SeguroSaude', 'mailContentType') );
    /**********************************************
    *
    *    Frontend Hooks
    *
    **********************************************/
    add_action( "wp_ajax_nopriv_process_form", array('SeguroSaude', 'processForm') );
    add_action( "wp_ajax_process_form", array('SeguroSaude', 'processForm') );
    add_action('wp_enqueue_scripts', array('SeguroSaude', 'loadFrontendScripts'));
  }

  private static function installTables()
  {
    global $wpdb;
    // creating planos table
    $table_name_planos = $wpdb->prefix . 'calc_ss_planos';
    $table_name_modalidades = $wpdb->prefix . 'calc_ss_modalidades';
    $table_name_categorias = $wpdb->prefix . 'calc_ss_categories';
    $table_name_status = $wpdb->prefix . 'calc_ss_status';
    $table_name_age_by_price = $wpdb->prefix . 'calc_ss_age_by_price';
    $table_name_modalidades_has_categories = $wpdb->prefix . 'calc_ss_modalidades_has_categories';
    $table_name_leads = $wpdb->prefix . 'calc_ss_leads';
    $table_name_forms = $wpdb->prefix . 'calc_ss_forms';
    $charset_collate = $wpdb->get_charset_collate();

    $planos = "CREATE TABLE $table_name_planos (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255) NULL,
      slug VARCHAR(255) NULL,
      modalidades INT NULL,
      created_at DATETIME NOT NULL DEFAULT NOW(),
      updated_at DATETIME NULL,
      PRIMARY KEY (id)
    ) $charset_collate;";

    $modalidades = "CREATE TABLE $table_name_modalidades (
      `id` INT NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NULL,
      `total_age` INT NULL,
      `price_min` DECIMAL(15,2) NULL,
      `price_max` DECIMAL(15,2) NULL,
      `created_at` DATETIME NOT NULL DEFAULT NOW(),
      `categorias` VARCHAR(255) NULL,
      `updated_at` DATETIME NULL,
      `planos_id` INT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `fk_modalidades_planos1_idx` (`planos_id` ASC),
      CONSTRAINT `fk_modalidades_planos1`
      FOREIGN KEY (`planos_id`)
      REFERENCES `$table_name_planos` (`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION
    ) $charset_collate;";

    $categorias = "CREATE TABLE $table_name_categorias (
      `id` INT NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NULL,
      `slug` VARCHAR(255) NULL,
      `created_at` DATETIME NOT NULL DEFAULT NOW(),
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

    $status = "CREATE TABLE $table_name_status (
      `id` INT NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NULL,
      `slug` VARCHAR(255) NULL,
      `created_at` DATETIME NOT NULL DEFAULT NOW(),
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`id`)
    ) $charset_collate;";

    $age_by_price = "CREATE TABLE $table_name_age_by_price (
      `id` INT NOT NULL AUTO_INCREMENT,
      `age_min` INT NULL,
      `age_max` INT NULL,
      `price_cop` DECIMAL(15,2) NULL,
      `price_nocop` DECIMAL(15,2) NULL,
      `modalidades_id` INT NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT NOW(),
      `updated_at` DATETIME NULL,
      PRIMARY KEY (`id`),
      INDEX `fk_age_by_price_modalidades_idx` (`modalidades_id` ASC),
      CONSTRAINT `fk_age_by_price_modalidades`
      FOREIGN KEY (`modalidades_id`)
      REFERENCES `$table_name_modalidades` (`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION
    ) $charset_collate;";

    $modalidades_has_categories = "CREATE TABLE $table_name_modalidades_has_categories (
      `modalidades_id` INT NOT NULL,
      `categorias_id` INT NOT NULL,
      PRIMARY KEY (`modalidades_id`, `categorias_id`),
      INDEX `fk_modalidades_has_categorias_categorias1_idx` (`categorias_id` ASC),
      INDEX `fk_modalidades_has_categorias_modalidades1_idx` (`modalidades_id` ASC),
      CONSTRAINT `fk_modalidades_has_categorias_modalidades1`
      FOREIGN KEY (`modalidades_id`)
      REFERENCES `$table_name_modalidades` (`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION,
      CONSTRAINT `fk_modalidades_has_categorias_categorias1`
      FOREIGN KEY (`categorias_id`)
      REFERENCES `$table_name_categorias` (`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION
    ) $charset_collate;";

    $leads = "CREATE TABLE $table_name_leads (
      `id` INT NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(255) NOT NULL,
      `email` VARCHAR(255) NOT NULL,
      `telefone` VARCHAR(255) NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT NOW(),
      `categorias_id` INT NOT NULL,
      `ages_selected` TEXT NULL,
      `status_id` INT DEFAULT NULL,
      `obs` TEXT NULL,
      `responsible` INT NULL,
      PRIMARY KEY (`id`),
      INDEX `fk_leads_categorias1_idx` (`categorias_id` ASC),
      INDEX `fk_leads_status1_idx` (`status_id` ASC),
      CONSTRAINT `fk_leads_categorias1`
      FOREIGN KEY (`categorias_id`)
      REFERENCES `$table_name_categorias` (`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION,
      CONSTRAINT `fk_leads_status1`
      FOREIGN KEY (`status_id`)
      REFERENCES `$table_name_status` (`id`)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
    ) $charset_collate;";

    $sql = [$planos, $modalidades, $categorias, $age_by_price, $modalidades_has_categories, $status, $leads];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
  }

  private static function randomUser()
  {
    $users = get_users(array(
      "fields" => array('id'),
      "role__in" => ['author', 'contributor', 'editor']
    ));
    $ids = array();
    foreach ($users as $user) {
      array_push($ids, $user->id);
    }
    $randKey = array_rand($ids, 1);
    return intval($ids[$randKey]);
  }

  private static function filterPlan($plan)
  {
    $prepared = array(
      "id" => $plan[0]->id,
      "name" => $plan[0]->name,
      "slug" => $plan[0]->slug,
      "modalidade" => array(),
      "ages" => array(),
      "prices" => array(),
      "categories" => array()
    );

    foreach ($plan as $key => $value) {
      $modalidade = array(
        "id" => $value->modalidade_id,
        "value" => $value->modalidade
      );

      $ages = array(
        "min" => $plan[$key]->age_min,
        "max" => $plan[$key]->age_max
      );

      $prices = array(
        "price_cop" => $plan[$key]->price_cop,
        "price_nocop" => $plan[$key]->price_nocop
      );

      if (!in_array($modalidade, $prepared['modalidade'])) {
        array_push($prepared['modalidade'], $modalidade);  
      }

      if (!in_array($ages, $prepared['ages'])) {
        array_push($prepared['ages'], $ages);  
      }

      if (!in_array($prices, $prepared['prices'])) {
        array_push($prepared['prices'], $prices);  
      }

      if (!in_array($value->categoria_id, $prepared['categories'])) {
        array_push($prepared['categories'], $value->categoria_id);  
      }
    }

    foreach ($prepared['modalidade'] as $key => $modalidade) {
      $categorias = array();
        // foreach ($item as $value) {
        //   if ($modalidade['value'] == $value->modalidade) {
        //     if (!in_array($value->categoria_id, $categorias)) {
        //       array_push($categorias, $value->categoria_id);
        //     }
        //   }
        // };
      $prepared['modalidade'][$key]['categorias'] = join($categorias,',');
    }

    return $prepared;
  }

  private static function filterPlan2($plan, $parsed)
  {
    $prepared = array(
      "id" => $plan[0]->id,
      "name" => $plan[0]->name,
      "slug" => $plan[0]->slug,
      "modalidade" => array()
    );

    $agesArr = array();
    $pricesArr = array();
    $catArr = array();

    $countModalidade = 0;

    foreach ($plan as $key => $value) {
      $modalidade = array(
        "id" => $value->modalidade_id,
        "value" => $value->modalidade
      );

      $ages = array(
        "min" => $plan[$key]->age_min,
        "max" => $plan[$key]->age_max
      );

      $prices = array(
        "price_cop" => $plan[$key]->price_cop,
        "price_nocop" => $plan[$key]->price_nocop
      );

      if (!in_array($modalidade, $prepared['modalidade'])) {
        array_push($prepared['modalidade'], $modalidade);  
      }

      if (!in_array($ages, $agesArr)) {
        array_push($agesArr, $ages);  
      }

      if (!in_array($prices, $pricesArr)) {
        array_push($pricesArr, $prices);  
      }

      if (isset($value->categoria_id)) {
        if (!in_array($value->categoria_id, $catArr)) {
          array_push($catArr, $value->categoria_id);  
        }
      }
    }

    foreach ($prepared['modalidade'] as $key => $modalidade) {
      $categorias = array();
      foreach ($agesArr as $age) {
        $prepared['modalidade'][$key]['ages'][] = $age;
      }
      foreach ($pricesArr as $price) {
        $prepared['modalidade'][$key]['prices'][] = $price;
      }

      $prepared['modalidade'][$key]['categorias'] = join($catArr,',');
    }

    $ageKey = $parsed['ss-amb1-age'][$prepared['modalidade'][0]['ages'][0]['min'].'__'.$prepared['modalidade'][0]['ages'][0]['max']];
    $prepared['modalidade'][0]['prices'][0]['price_cop'] = floatval($prepared['modalidade'][0]['prices'][0]['price_cop'] * $ageKey);
    $prepared['modalidade'][0]['prices'][0]['price_nocop'] = floatval($prepared['modalidade'][0]['prices'][0]['price_nocop'] * $ageKey);

    return $prepared;
  }

  public static function in_array_r($needle, $haystack, $strict = false) 
  {
      foreach ($haystack as $item) {
          if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
              return true;
          }
      }

      return false;
  }

}
