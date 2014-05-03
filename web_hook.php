<?php
	date_default_timezone_set('America/Sao_Paulo');

	function indent($json) {
	    $result      = '';
	    $pos         = 0;
	    $strLen      = strlen($json);
	    $indentStr   = '  ';
	    $newLine     = "\n";
	    $prevChar    = '';
	    $outOfQuotes = true;

	    for ($i=0; $i<=$strLen; $i++) {

	        // Grab the next character in the string.
	        $char = substr($json, $i, 1);

	        // Are we inside a quoted string?
	        if ($char == '"' && $prevChar != '\\') {
	            $outOfQuotes = !$outOfQuotes;

	        // If this character is the end of an element,
	        // output a new line and indent the next line.
	        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
	            $result .= $newLine;
	            $pos --;
	            for ($j=0; $j<$pos; $j++) {
	                $result .= $indentStr;
	            }
	        }

	        // Add the character to the result string.
	        $result .= $char;

	        // If the last character was the beginning of an element,
	        // output a new line and indent the next line.
	        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
	            $result .= $newLine;
	            if ($char == '{' || $char == '[') {
	                $pos ++;
	            }

	            for ($j = 0; $j < $pos; $j++) {
	                $result .= $indentStr;
	            }
	        }

	        $prevChar = $char;
	    }

	    return $result;
	}


	// Init vars
	$foo 				= '{"repository": {"website": "", "fork": false, "name": "Shoptime - Joga Junto", "scm": "git", "owner": "trsouz", "absolute_url": "/trsouz/shoptime-joga-junto/", "slug": "shoptime-joga-junto", "is_private": true}, "truncated": false, "commits": [{"node": "15f90af4eef1", "files": [{"type": "modified", "file": "app/application/config/fb_ignited.php"}], "raw_author": "Thiago Rodrigues <thiagorsouz@gmail.com>", "utctimestamp": "2014-05-03 15:24:17+00:00", "author": "trsouz", "timestamp": "2014-05-03 17:24:17", "raw_node": "15f90af4eef14ed46ada4e2963538a5e99637bfc", "parents": ["abc4b0a3c5e6"], "branch": "master", "message": "alterando infos do app\n", "revision": null, "size": -1}], "canon_url": "https://bitbucket.org", "user": "trsouz"}';
	
	if(isset($HTTP_RAW_POST_DATA)){
		$RAW 				= $HTTP_RAW_POST_DATA;
		$JSON 				= json_decode($RAW);
		if (isset($JSON->ref)){
			$DESIRED_BRANCH     = explode('/', $JSON->ref);
			$DESIRED_BRANCH     = end($DESIRED_BRANCH);
		}
		if (isset($JSON->repository)){
			$LOCAL_REPO_NAME = $JSON->repository->name;
			$REMOTE_REPO = $JSON->repository->url;
		}
	}else if(isset($_POST['payload']) || $foo){
		$RAW 				= $foo;
		$JSON 				= json_decode($RAW);
		if (isset($JSON->commits) && count($JSON->commits)){
			$DESIRED_BRANCH     = array_shift($JSON->commits);
			$DESIRED_BRANCH     = $DESIRED_BRANCH->branch;
		}

		if (isset($JSON->canon_url, $JSON->repository, $JSON->repository->owner, $JSON->repository->slug)){
			$LOCAL_REPO_NAME = $JSON->repository->slug;
			$REMOTE_REPO = str_replace("https://", "git@", $JSON->canon_url) . ':' . $JSON->repository->owner . '/' . $JSON->repository->slug . '.git';
		}
	}

	if(!isset($DESIRED_BRANCH, $LOCAL_REPO_NAME, $REMOTE_REPO)){
		exit;
	}

	$LOCAL_ROOT         = "apps";
	$LOCAL_BRANCH       = $LOCAL_ROOT . DIRECTORY_SEPARATOR . $DESIRED_BRANCH;
	$LOCAL_REPO         = $LOCAL_BRANCH . DIRECTORY_SEPARATOR . $LOCAL_REPO_NAME;


	// Create folder root
	if (!file_exists($LOCAL_ROOT)) {
	    shell_exec("mkdir {$LOCAL_ROOT}");
	}

	// Create folder by branch
	if (!file_exists($LOCAL_BRANCH)) {
	    shell_exec("mkdir {$LOCAL_BRANCH}");
	}

	//create history file
	$fp = fopen("{$LOCAL_BRANCH}/{$LOCAL_REPO_NAME}.txt", 'a');
	fwrite($fp, "\n\n\n" . date("d-m-Y H:i:s") . "\n");
	fwrite($fp, indent($RAW));
	fclose($fp);

	if( file_exists($LOCAL_REPO) ) {
		shell_exec("cd {$LOCAL_REPO} && git reset --hard HEAD && git checkout {$DESIRED_BRANCH} && git pull ");
	} else {
		shell_exec("cd {$LOCAL_BRANCH} && git clone {$REMOTE_REPO} {$LOCAL_REPO_NAME} -b {$DESIRED_BRANCH}");
	}
	die("done " . time());
	// Clone fresh repo from github using desired local repo name and checkout the desired branch
	//shell_exec("cd {$LOCAL_BRANCH} && git clone {$REMOTE_REPO} {$LOCAL_REPO_NAME} && cd {$LOCAL_REPO} && git checkout {$DESIRED_BRANCH}");
?>

