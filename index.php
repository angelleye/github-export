<?php

$owner = 'angelleye';
$repo = 'paypal-woocommerce';

/* Get Milestone array */
$milestone_info_json = get_github_json_data_from_url('https://api.github.com/repos/'.$owner.'/'.$repo.'/milestones');
$milestone_info_array = json_decode($milestone_info_json, true);

if(isset($milestone_info_array['message']) && !empty($milestone_info_array['message'])){
    echo $milestone_info_array['message'];
    exit;
}

foreach ($milestone_info_array as $value) {
    $jira_array_milestones[] = array('name' => $value['title']);
}

$FinalArray['milestones'] = $jira_array_milestones;
$FinalArray['attachments'] = array();

/* We have version and milestone same so we are adding same data in it */
$FinalArray['versions'] = $jira_array_milestones;



    
/* Get Project basic information */
$project_info_json = get_github_json_data_from_url('https://api.github.com/repos/'.$owner.'/'.$repo);
$project_info_array = json_decode($project_info_json, true);
if(isset($project_info_array['message']) && !empty($project_info_array['message'])){
    echo $project_info_array['message'];
    exit;
}

$jira_array = array(
    'projects' => array(
        array(
            'name' => ucwords(str_replace('-', ' ', $project_info_array['name'])),
            'key' => 'PFW',
            'description' => $project_info_array['description'],
            'type' => 'software',
            'template' => 'com.pyxis.greenhopper.jira:gh-scrum-template'
        )
    )
);

for($k=1;$k<=10;$k++){
    
    $page_number = $k;
    
    /* Call this API for fetching list  of issues */
    $github_json = get_github_json_data_from_url('https://api.github.com/repos/'.$owner.'/'.$repo.'/issues?page='.$page_number);
    $github_array = json_decode($github_json, true);
    if(isset($github_array['message']) && !empty($github_array['message'])){
        echo $github_array['message'];
        exit;
    }


    $i = 0;
    /* loop through all the issues */
    foreach ($github_array as $value) {
    //    if ($i == 2) {
    //        break;
    //    }
        $issue_number = $value['number'];
        /* Call API to get issue details by issue numbers */
        $issue_json = get_github_json_data_from_url('https://api.github.com/repos/'.$owner.'/'.$repo.'/issues/' . $issue_number);
        $issue_array = json_decode($issue_json, true);

        if(isset($issue_array['message']) && !empty($issue_array['message'])){
            echo $issue_array['message'];
            exit;
        }

        $assignees = $issue_array['assignees'];
        $watchers = array();
        foreach ($assignees as $a) {
            $watchers[] = $a['login'];
        }
        
        if(isset($issue_array['labels'][0]['name']) && $issue_array['labels'][0]['name'] =='feature request'){
            $issueType = 'Feature';            
        }
        else{
            $issueType = isset(ucfirst($issue_array['labels'][0]['name'])) ? ucfirst($issue_array['labels'][0]['name']) : 'Feature';
        }
        $issues[] = array(
            "status" => isset($issue_array['state']) ? ucfirst($issue_array['state']) : 'Open',
            "priority" => "major",
            "issuetype" => $issueType,
            "content_updated_on" => null,
            "voters" => array(),
            "summary" => $issue_array['title'],
            "reporter" => $issue_array['user']['login'],
            "component" => null,
            "watchers" => array_unique($watchers),
            "description" => $issue_array['body'],
            "assignee" => $issue_array['assignee']['login'],
            "created_on" => date("o\-m\-d\TH\:i\:s\.uP", strtotime($issue_array['created_at'])),
            "version" => $issue_array['milestone']['title'],
            "edited_on" => null,
            "milestone" => $issue_array['milestone']['title'],
            'updated_on' => date("o\-m\-d\TH\:i\:s\.uP", strtotime($issue_array['updated_at'])),
            "id" => $issue_number
        );

        /* Call API to get comments by issue numbers */
        $comments_json = get_github_json_data_from_url('https://api.github.com/repos/'.$owner.'/'.$repo.'/issues/'.$issue_number.'/comments');
        $comments_array = json_decode($comments_json, true);

        if(isset($issue_array['message']) && !empty($issue_array['message'])){
            echo $issue_array['message'];
            exit;
        }

        //$jira_array_comments = array();
        foreach ($comments_array as $cm) {
            $comm = array(
                'content' => $cm['body'],
                'created_on' =>  date("o\-m\-d\TH\:i\:s\.uP", strtotime($cm['created_at'])) ,
                'user' => $cm['user']['login'],
                'updated_on' => date("o\-m\-d\TH\:i\:s\.uP", strtotime($cm['updated_at'])),
                'issue' => $issue_number,
                'id' => $cm['id']);
                $jira_array_comments[]= $comm;
        }

        $i++;
    }
}

$jira_array['projects'][0]['issues'] = $issues;

$FinalArray['comments'] = $jira_array_comments;
$FinalArray['meta'] = array(
    'default_milestone' => null,
    'default_assignee' => null,
    "default_kind" => "bug",
    "default_component" => null,
    "default_version" => "1.4.9"
);
$FinalArray['components'] = array();
$FinalArray['projects']=$jira_array['projects'];
$FinalArray['logs'] = array();

$finalJson = json_encode($FinalArray);
?>
<button onclick="prettyPrint()">Pretty Print</button> <br><br><br>
<textarea id="myTextArea" cols=50 rows=30><?php echo $finalJson; ?></textarea>

<script>
function prettyPrint() {
    var ugly = document.getElementById('myTextArea').value;
    var obj = JSON.parse(ugly);
    var pretty = JSON.stringify(obj, undefined, 4);
    document.getElementById('myTextArea').value = pretty;
}
</script>
<?php
function get_github_json_data_from_url($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => [
            "Authorization: Basic dGVqYXNtLWl0cGF0aDppcHMxMjM0NQ==",
            "Accept: application/vnd.github.v3+json",
            "Content-Type: application/json",
            "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 YaBrowser/16.3.0.7146 Yowser/2.5 Safari/537.36"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    if ($err) {
        echo "cURL Error #:" . $err;
        exit;
    } else {
        return $response;
    }
}
