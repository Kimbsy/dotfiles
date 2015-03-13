<?php

/**
 * Generate xml file (as string) and echo (std out)
 *
 * FEC calulation translated from simsitive_wlm/brunch/app/includes/Calc/calc.coffee
 * 
 * Usage:
 * 
 *   drush scr xml_generator_ucc.php > xml_data_ucc.xml
 *   
 */

//////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////// GET DATA //////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////

$info = array();

$year = 1;

// get all the employees
$query = db_select('users', 'u');
// $query->fields('u', array('uid'));
$query->condition('u.uid', 148, '=');

// Employee No
$query->leftJoin('field_data_field_employee_no', 'e', 'u.uid = e.entity_id');
$query->addField('e', 'field_employee_no_value', 'Employee No');

// Forename
$query->leftJoin('field_data_field_forename', 'ff', 'u.uid = ff.entity_id');
$query->addField('ff', 'field_forename_value', 'Forename');

// Surname
$query->leftJoin('field_data_field_surname', 'fs', 'u.uid = fs.entity_id');
$query->addField('fs', 'field_surname_value', 'Surname');

// Gender
$query->leftJoin('field_data_field_gender', 'g', 'u.uid = g.entity_id');
$query->addField('g', 'field_gender_value', 'Gender');

// College
//
//

// Management Unit
$query->leftJoin('field_data_field_management_unit', 'mu', 'u.uid = mu.entity_id');
$query->addField('mu', 'field_management_unit_value', 'Management Unit');

// Department
$query->leftJoin('field_data_field_department', 'd', 'u.uid = d.entity_id');
$query->addField('d', 'field_department_value', 'Department');

// Workgroup
$query->leftJoin('field_data_field_workgroup', 'wg', 'u.uid = wg.entity_id');
$query->addField('wg', 'field_workgroup_tid', 'Workgroup');

// Date Started
$query->leftJoin('field_data_field_job_start', 'js', 'u.uid = js.entity_id');
$query->addField('js', 'field_job_start_value', 'Date Started');

// Active Indicator
//
//

// Completion Status
//
//

// Stage
//
//

// User Status
$query->addField('u', 'status', 'User Status');

// FEC Category
$query->leftJoin('field_data_field_academic_category_fec', 'fec', 'u.uid = fec.entity_id');
$query->addField('fec', 'field_academic_category_fec_value', 'FEC Category');

// FEC Grade
//
//

// Primary Job Role
$query->leftJoin('field_data_field_job_primary', 'jp', 'u.uid = jp.entity_id');
$query->addField('jp', 'field_job_primary_tid', 'Primary Job Role');

// Post title
$query->leftJoin('field_data_field_post_title', 'pt', 'u.uid = pt.entity_id');
$query->addField('pt', 'field_post_title_value', 'Post Title');


// FTE Band
$query->leftJoin('field_data_field_fte', 'fte', 'u.uid = fte.entity_id');
$query->addField('fte', 'field_fte_value', 'FTE Band');

// Last Login
$query->addField('u', 'login', 'Last Login');

// Reports to Employee No
$query->leftJoin('field_data_field_reports_to', 'r2', 'u.uid = r2.entity_id');
$query->addField('r2', 'field_reports_to_value', 'Reports To Employee No');


// reports to uid is r2e.entity_id
$query->leftJoin('field_data_field_employee_no', 'r2e', 'r2e.field_employee_no_value = r2.field_reports_to_value');

// Forename
$query->leftJoin('field_data_field_forename', 'r2ff', 'r2e.entity_id = r2ff.entity_id');
$query->addField('r2ff', 'field_forename_value', 'Reports To Forename');

// Surname
$query->leftJoin('field_data_field_surname', 'r2fs', 'r2e.entity_id = r2fs.entity_id');
$query->addField('r2fs', 'field_surname_value', 'Reports To Surname');

// Requested verification indicator
// 
// 

// get user data from simitive_wlm_answers
$query->leftJoin('simitive_wlm_answers', 'swlma', 'u.uid = swlma.uid');
// make sure we are only looking at the correct year
$query->condition('swlma.year', $year, '=');
$query->fields('swlma', array('data'));

$info = $query->execute()->fetchAll(PDO::FETCH_ASSOC);

// horrible map for figuring out where subtotals should go
// regex fragment => subtotal
$sub_map = array(
  'q-teaching-1' => 'Lecturing- Undergraduate/Postgraduate',
  'q-teaching-2' => 'Laboratory Practical/Enterprise/Practitioner Activity',
  'q-teaching-3' => 'Clinical Practice/Education',
  'q-teaching-4' => 'Tutoring',
  'q-teaching-5' => 'Supervision',
  'q-teaching-6' => 'Examining & Assessment',
  'q-research-1' => 'General Research Allocation',
  'q-research-2' => 'Conference/Symposium Activities',
  'q-research-3' => 'Publications and disseminating Activities',
  'q-research-4' => 'Research Leadership/Management of Research',
  'q-research-5' => 'Research Project Applications',
  'q-research-6' => 'Research Project Activity/Commercialisation',
  'q-prof-1' => 'Conference/Symposium Organisation',
  'q-prof-2' => 'External Activity (External Examining, External Professional Review)',
  'q-prof-3' => 'Academic Editing & Review',
  'q-prof-4' => 'Consultancy/Professional/Income-Generating Activities',
  //////////////////////////////////////////////////////////////////////////////////////////////////
  ///
  ///
  /// this one might be in the wrong place?
  ///
  ///
  ///VVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVVV
  'q-prof-5' => 'Clinical Services (Related to Health Services)',
  'q-prof-6' => 'National/International Policy Development',
  'q-prof-7' => 'Public Engagement',
  'q-prof-8' => 'Personal Academic Activities',
  'q-academic-1' => 'General Administration',
  'q-academic-2' => 'Pastoral Care/Student Support',
  'q-academic-3' => 'University Level Administration',
  'q-academic-4' => 'College Level Administration',
  'q-academic-5' => 'School Level Administration',
  'q-academic-6' => 'Discipline/Department/Academic Unit Level Administration',
  'q-academic-7' => 'General Outreach',
);

// im so sorry
$group_map = array(
  'teaching' => 'Teaching',
  'research' => 'Research',
  'prof' => 'Professional Academic Service',
  'academic' => 'Academic Administration',
);

// for each user, grab their serialized, compressed encoded data; then decode, express and unserialize it.
// 
// .... Matt, come on man.
foreach ($info as &$user) {
  $user['data'] = ig_core_decode($user['data']);

  // get the neccessary info from this data
 
  // print_r($user);

  $subtotals = array(
    'Teaching' => array(
      'Lecturing- Undergraduate/Postgraduate'                 => 0,
      'Laboratory Practical/Enterprise/Practitioner Activity' => 0,
      'Clinical Practice/Education'                           => 0,
      'Tutoring'                                              => 0,
      'Supervision'                                           => 0,
      'Examining & Assessment'                                => 0,
    ),
    'Research' => array(
      'General Research Allocation'                 => 0,
      'Conference/Symposium Activities'             => 0,
      'Publications and disseminating Activities'   => 0,
      'Research Leadership/Management of Research'  => 0,
      'Research Project Applications'               => 0,
      'Research Project Activity/Commercialisation' => 0,
    ),
    'Professional Academic Service' => array(
      'Conference/Symposium Organisation'                                    => 0,
      'External Activity (External Examining, External Professional Review)' => 0,
      'Academic Editing & Review'                                            => 0,
      'Consultancy/Professional/Income-generating activities'                => 0,
      'Clinical Services (related to Health Services)'                       => 0,
      'National/International Policy Development'                            => 0,
      'Public Engagement'                                                    => 0,
      'Personal Academic Activities'                                         => 0,
    ),
    'Academic Administration' => array(
      'General Administration'                                   => 0,
      'Pastoral Care/Student Support'                            => 0,
      'University Level Administration'                          => 0,
      'College Level Administration'                             => 0,
      'School Level Administration'                              => 0,
      'Discipline/Department/Academic Unit Level Administration' => 0,
      'General Outreach'                                         => 0,
    ),
  );

  $totals = array(
    'teaching'         => 0,
    'teaching#ug-lue'  => 0,
    'teaching#pgt-lue' => 0,
    'teaching#pgr-lue' => 0,
    'research'         => 0,
    'research#lue'     => 0,
    'academic'         => 0,
    'academic#lue'     => 0,
    'prof'             => 0,
    'prof#lue'         => 0,
    'other'            => 0,
    'other#lue'        => 0,
    'summaries'        => 0,
    'total'            => 0,
  );

  // fec summaries array
  $summaries = array();

  foreach ($user['data'] as $key => $value) {

    $hash_frag = explode('#', $key);
    $fragment = explode('-', $hash_frag[0]);

    // my bit, this is disgusting
    foreach ($sub_map as $substr => $str) {
      if (strpos($hash_frag[0], $substr) !== FALSE) {
        $subtotals[$group_map[$fragment[1]]][$sub_map[$substr]] += $user['data'][$key]['value'];
      }
    }

    if (!isset($totals[$fragment[1]])) {
      $totals[$fragment[1]] = 0;
    }

    $totals[$fragment[1]] += (float) $user['data'][$key]['value'];

    if (!strpos($key, 'summaries') > 0) {
      if (!isset($totals['total'])) {
        $totals['total'] = 0;
      }
      $totals['total'] += (float) $user['data'][$key]['value'];
    }

    if (isset($hash_frag[1])) {
      if (!isset($totals[$fragment[1] . '#' . $hash_frag[1]])) {
        $totals[$fragment[1] . '#' . $hash_frag[1]] = 0;
      }
      $totals[$fragment[1] . '#' . $hash_frag[1]] += (float) $user['data'][$key]['value'];
    }

    // get fec summaries data
    if ($fragment[1] == 'summaries') {
      $summaries[str_replace('q-summaries-', '', $key)] = $value['value'];
    }
  }

  // print_r($summaries);  
  // print_r($totals);
  // print_r($subtotals);

  // Stick totals and subtotals in the user array

  foreach ($totals as $key => $value) {
    if (array_key_exists($key, $group_map)) {
      $subtotals[$group_map[$key]]['total'] = $value;
    }
  }

  $user['information'] = $subtotals;



  // calculate FEC summaries
  // 
  // 

  $ac_ad_te = isset($summaries['academic-admin-teaching']) ? $summaries['academic-admin-teaching'] / 100 : 0;
  $ot_ac_te = isset($summaries['other-activities-teaching']) ? $summaries['other-activities-teaching'] / 100 : 0;

  $ac_ad_re = isset($summaries['academic-admin-research']) ? $summaries['academic-admin-research'] / 100 : 0;
  $ot_ac_re = isset($summaries['other-activities-research']) ? $summaries['other-activities-research'] / 100 : 0;

  $ac_ad_pr = isset($summaries['academic-admin-prof']) ? $summaries['academic-admin-prof'] / 100 : 0;
  $ot_ac_pr = isset($summaries['other-activities-prof']) ? $summaries['other-activities-prof'] / 100 : 0;

  $ac_ad_cl = isset($summaries['academic-admin-clinical']) ? $summaries['academic-admin-clinical'] / 100 : 0;
  $ot_ac_cl = isset($summaries['other-activities-clinical']) ? $summaries['other-activities-clinical'] / 100 : 0;

  $ac_ad_ad = isset($summaries['academic-admin-general']) ? $summaries['academic-admin-general'] / 100 : 0;
  $ot_ac_ad = isset($summaries['other-activities-general']) ? $summaries['other-activities-general'] / 100 : 0;

  $sp_re = isset($summaries['research-sponsored']) ? $summaries['research-sponsored'] / 100 : 0;
  $un_re = isset($summaries['research-unsponsored']) ? $summaries['research-unsponsored'] / 100 : 0;

  $q_prof_4_1 = isset($user['data']['q-prof-4-1#lue']['value']) ? $user['data']['q-prof-4-1#lue']['value'] : 0;
  $q_prof_4_2 = isset($user['data']['q-prof-4-2#lue']['value']) ? $user['data']['q-prof-4-2#lue']['value'] : 0;
  $q_prof_4_3 = isset($user['data']['q-prof-4-3#lue']['value']) ? $user['data']['q-prof-4-3#lue']['value'] : 0;

  // stupid unexplained formula
  $g21 = $totals['academic'] * $ac_ad_pr;
  $j37 = $totals['prof'] - $q_prof_4_1 - $q_prof_4_2 - $q_prof_4_3;
  $k6  = $q_prof_4_1 + $q_prof_4_3;

  // arrays relating to each FEC section
  $undergrad_teaching = array(
    $totals['teaching#ug-lue'],
    $totals['academic'] * $ac_ad_te * $totals['teaching#ug-lue'] / $totals['teaching'],
    $totals['other'] * $ot_ac_te * $totals['teaching#ug-lue'] / $totals['teaching'],
  );

  $postgrad_taught_teaching = array(
    $totals['teaching#pgt-lue'],
    $totals['academic'] * $ac_ad_te * $totals['teaching#pgt-lue'] / $totals['teaching'],
    $totals['other'] * $ot_ac_te * $totals['teaching#pgt-lue'] / $totals['teaching'],
  );

  $postgrad_research_teaching = array(
    $totals['teaching#pgr-lue'],
    $totals['academic'] * $ac_ad_te * $totals['teaching#pgr-lue'] / $totals['teaching'],
    $totals['other'] * $ot_ac_te * $totals['teaching#pgr-lue'] / $totals['teaching'],
  );
  
  $sponsored_research = array(
    $totals['research'] * $sp_re,
    $totals['academic'] * $ac_ad_re * $sp_re,
    $totals['other'] * $ot_ac_re * $sp_re,
  );

  $unsponsored_research = array(
    $totals['research'] * $un_re,
    $totals['academic'] * $ac_ad_re * $un_re,
    $totals['other'] * $ot_ac_re * $un_re,
  );

  $other_scholarly_activity = array(
    $j37,
    $g21 * $j37 / ($j37 + $k6),
    $totals['other'] * $ot_ac_pr * ($j37 / ($j37 + $k6)),
  );

  $other_income_activities = array(
    $k6,
    $g21 * $k6 / ($j37 + $k6),
    $totals['other'] * $ot_ac_pr * ($k6 / ($j37 + $k6)),
  );

  $clinical_services = array(
    $q_prof_4_2,
    $totals['academic'] * $ac_ad_cl,
    $totals['other'] * $ot_ac_cl,
  );

  $administration = array(
    $totals['academic'] * $ac_ad_ad,
    $totals['other'] * $ot_ac_ad,
  );

  // final percentages are sums of each FEC array
  $user['information']['FECSummaries'] = array(
    'UndergraduateTeaching' => round((array_sum($undergrad_teaching) / $totals['total'] * 100), 2),
    'PostgraduateTaughtTeaching' => round((array_sum($postgrad_taught_teaching) / $totals['total'] * 100), 2),
    'PostgraduateResearchTeaching' => round((array_sum($postgrad_research_teaching) / $totals['total'] * 100), 2),
    'SponsoredResearch' => round((array_sum($sponsored_research) / $totals['total'] * 100), 2),
    'UnsponsoredResearch' => round((array_sum($unsponsored_research) / $totals['total'] * 100), 2),
    'OtherScholarlyActivity' => round((array_sum($other_scholarly_activity) / $totals['total'] * 100), 2),
    'OtherIncomeGeneratingActivities' => round((array_sum($other_income_activities) / $totals['total'] * 100), 2),
    'ClinicalServicesRelatedtoHealthServices' => round((array_sum($clinical_services) / $totals['total'] * 100), 2),
    'Administration' => round((array_sum($administration) / $totals['total'] * 100), 2),
  );

  $FEC_total = array_sum($user['information']['FECSummaries']);

  $user['information']['FECSummaries']['total'] = $FEC_total;

  // then clean up the array before it goes into the xml generator

  unset($user['data']);
}

// print_r($info);


//////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////// CREATE XML /////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////

// set header
header('Content-type: text/xml');

// create new SimpleXML object
$xml_info = new SimpleXMLElement('<?xml version="1.0"?><info></info>');

// convert data into XML
array_to_xml($info, $xml_info);

// make xml file pretty
$dom = new DOMDocument("1.0");

$dom->preserveWhiteSpace = false;

$dom->formatOutput = true;

$dom->loadXML($xml_info->asXML());

// echo to terminal
echo $dom->saveXML();



// recursive function (which means it's recursive) to convert any depth array into xml
function array_to_xml($info, &$xml_info) {
  foreach ($info as $key => $value) {
    // remove characters that XLM can't handle
    $safe_key = str_replace(array(' ', '-', '/', '&', '(', ')', ','), array('', '', '', '', '', '', ''), $key);

    // do we need to go deeper?
    if (is_array($value)) {

      // numbers dont make good key names
      if (!is_numeric($key)) {
        $subnode = $xml_info->addChild($safe_key);
        // recurse
        array_to_xml($value, $subnode);
      }
      else {
        $subnode = $xml_info->addChild('User' . $key);
        // recurse
        array_to_xml($value, $subnode);
      }
    }
    else {
      // add element to xml
      $xml_info->addChild($safe_key, htmlspecialchars($value));
    }
  }
}
