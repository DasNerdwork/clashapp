<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/mongo-db.php';
include_once('/hdd1/clashapp/update.php');

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Data Validation checks
if(isset($_POST['csrf_token'])){
    if(!isValidCSRF($_POST['csrf_token'])){
        die("Invalid csrf_token: " . $_POST['csrf_token']);
    }
}
if(isset($_POST['iteration'])){
    if(!isValidIterator($_POST['iteration'])){
        die("Invalid iterator: " . $_POST['iteration']);
    }
}
if(isset($_POST['name'])){
    if(!isValidPlayerName($_POST['name'])){
        die("Invalid playerName: " . $_POST['name']);
    }
}
if(isset($_POST['tag'])){
    if(!isValidPlayerTag($_POST['tag'])){
        die("Invalid tag: " . $_POST['tag']);
    }
}
if(isset($_POST['sumid'])){
    if(!isValidID($_POST['sumid'])){
        die("Invalid sumid: " . $_POST['sumid']);
    }
}
if(isset($_POST['teamid'])){
    if(!isValidID($_POST['teamid'])){
        die("Invalid teamid: " . $_POST['teamid']);
    }
}
if(isset($_POST['queuedas'])){
    if(!isValidPosition($_POST['queuedas'])){
        die("Invalid position: " . $_POST['queuedas']);
    }
}
if(isset($_POST['reload'])){
    if(($_POST['reload'] != 1) && $_POST['reload'] != ''){
        die("Invalid or non-empty reload value: " . $_POST['reload']);
    }
}
// End of Data Validation checks

if(isset($_POST['name'])){
    $iteration = "1";
    $playerName = $_POST['name'];
    $playerTag = $_POST['tag'];
    $tempSumid = $_POST['sumid'] ?? "";
    $mdb = new MongoDBHelper();
    $playerDataRequest = $mdb->getPlayerByRiotId($playerName, $playerTag);
}

if(isset($_POST['sumid'])){
    $iteration = $_POST['iteration'];
    $tempSumid = $_POST['sumid'];
    $teamID = $_POST['teamid'];
    $queuedAs = $_POST['queuedas'];
    $mdb = new MongoDBHelper();
    $playerDataRequest = $mdb->getPlayerBySummonerId($tempSumid);
}

if(isset($_POST['sumid']) || isset($_POST['name'])){
    $forceReload = $_POST['reload'];
    $responseArray = array();
    $scriptContent = "";
    $rankedContent = "";
    $masteryContent = "";
    $tagList = "";
    $matchHistoryContent = "";
    $recalculateSuggestedBanData = false;
    $upToDate = false;
    global $currentPatch;
    if($playerDataRequest["success"]){
        if(isset($_POST['sumid'])){
            if($mdb->findDocumentByField('teams', 'TeamID', $teamID)["success"]){
                $tempTeamJSON = $mdb->findDocumentByField('teams', 'TeamID', $teamID)["document"];
            }
        }
        $playerDataJSONString = json_encode($playerDataRequest["data"]);
        $playerDataJSON = json_decode($playerDataJSONString, true);
        if(isset($tempTeamJSON) && (((time() - $tempTeamJSON["LastUpdate"]) > 1800) || ($tempTeamJSON["LastUpdate"] == 0) || ($forceReload))){
            $tempMatchIDs = getMatchIDs($playerDataJSON["PlayerData"]["PUUID"], 15);                                           
            $matchInPlayerJsonButNotExistent = false;
            foreach(array_keys($playerDataJSON["MatchIDs"]) as $matchid) {
                if(!$mdb->findDocumentByField("matches", 'metadata.matchId', $matchid)["success"]){
                    $matchInPlayerJsonButNotExistent = true;
                }
            }
            if((array_keys($playerDataJSON["MatchIDs"])[0] != $tempMatchIDs[0]) || $matchInPlayerJsonButNotExistent){ // If first matchid is outdated -> call updateProfile below because $sumid is still unset from above
                $scriptContent .= "console.log('INFO: ".$playerDataJSON["PlayerData"]["GameName"]." was out-of-date -> Force updating.'); requests['".$tempSumid."'] = 'Pending';";
                $recalculateSuggestedBanData = true;
            } else {
                $playerName = $playerDataJSON["PlayerData"]["GameName"];
                $playerTag = $playerDataJSON["PlayerData"]["Tag"];
                $playerData = $playerDataJSON["PlayerData"];
                $sumid = $playerDataJSON["PlayerData"]["SumID"];
                $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                $rankData = $playerDataJSON["RankData"];
                $masteryData = $playerDataJSON["MasteryData"];
                $matchids = array_keys($playerDataJSON["MatchIDs"]);

                $scriptContent .= "console.log('".$playerName." already up to date.'); requests['".$tempSumid."'] = 'Done';";
                $recalculateMatchIDs = false;
                $recalculatePlayerLanes = false;
                foreach($playerDataJSON["MatchIDs"] as $singleMatchID => $score){
                    if($score == ""){
                        $recalculateMatchIDs = true;
                        break;
                    }
                }

                if(!isset($playerDataJSON["LanePercentages"]) || $playerDataJSON["LanePercentages"] == null){
                    $recalculatePlayerLanes = true;
                } else {
                    $playerLanes = $playerDataJSON["LanePercentages"];
                }

                $xhrMessage = "";
                
                if($recalculatePlayerLanes){
                    if($recalculateMatchIDs){
                        // If both player lanes and matchscores are missing/wrong in players .json
                        $xhrMessage = "mode=both&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid."&sumid=".$sumid;
                    } else {
                        // if only player lanes are missing/wrong
                        $xhrMessage = "mode=lanes&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid."&sumid=".$sumid;
                    }
                } elseif($recalculateMatchIDs){
                    // if only matchscores are wrong/missing
                    $xhrMessage = "mode=scores&matchids=".json_encode($playerDataJSON["MatchIDs"])."&sumid=".$sumid;
                }

                if($recalculatePlayerLanes || $recalculateMatchIDs){
                    $scriptContent .= "
                    ".processResponseData($iteration)."
                    var data = '".$xhrMessage."';
                    xhrAfter".$iteration.".send(data);
                    
                    ";
                } else {
                    $upToDate = true;
                    if($iteration == 5){ // Reset anti-request timer if all people are up to date and onAllFinish not called
                        // $tempTeamJSON["LastUpdate"] = time();
                        $mdb->addElementToDocument('teams', 'TeamID', $teamID, 'LastUpdate', time());
                    }
                }
            }
        } else {
            $playerName = $playerDataJSON["PlayerData"]["GameName"];
            $playerTag = $playerDataJSON["PlayerData"]["Tag"];
            $playerData = $playerDataJSON["PlayerData"];
            $sumid = $playerDataJSON["PlayerData"]["SumID"];
            $puuid = $playerDataJSON["PlayerData"]["PUUID"];
            $rankData = $playerDataJSON["RankData"];
            $masteryData = $playerDataJSON["MasteryData"];
            $matchids = array_keys($playerDataJSON["MatchIDs"]);

            $scriptContent .= "console.log('".$playerName." already up to date.'); requests['".$sumid."'] = 'Done';";
            $recalculateMatchIDs = false;
            $recalculatePlayerLanes = false;
            foreach($playerDataJSON["MatchIDs"] as $singleMatchID => $score){
                if($score == ""){
                    $recalculateMatchIDs = true;
                    break;
                }
            }

            if(!isset($playerDataJSON["LanePercentages"]) || $playerDataJSON["LanePercentages"] == null){
                $recalculatePlayerLanes = true;
            } else {
                $playerLanes = $playerDataJSON["LanePercentages"];
            }

            $xhrMessage = "";
            
            if($recalculatePlayerLanes){
                if($recalculateMatchIDs){
                    // If both player lanes and matchscores are missing/wrong in players .json
                    $xhrMessage = "mode=both&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid."&sumid=".$sumid;
                } else {
                    // if only player lanes are missing/wrong
                    $xhrMessage = "mode=lanes&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid;
                }
            } elseif($recalculateMatchIDs){
                // if only matchscores are wrong/missing
                $xhrMessage = "mode=scores&matchids=".json_encode($playerDataJSON["MatchIDs"])."&sumid=".$sumid;
            }

            if($recalculatePlayerLanes || $recalculateMatchIDs){
                $scriptContent .= "
                ".processResponseData($iteration)."
                var data = '".$xhrMessage."';
                xhrAfter".$iteration.".send(data);
                
                ";
            } else {
                $upToDate = true;
            }
        }
    }
    if(!isset($sumid) && $tempSumid != "") {
        $scriptContent .= "console.log('No playerfile found or out of date (".$tempSumid.")'); requests['".$tempSumid."'] = 'Pending';";
        $scriptContent .= updateProfile($tempSumid, $teamID, "sumid");
        $playerDataRequest = $mdb->getPlayerBySummonerId($tempSumid);
        if($playerDataRequest["success"]){
            $playerDataJSONString = json_encode($playerDataRequest["data"]);
            $playerDataJSON = json_decode($playerDataJSONString, true);
            $playerData = $playerDataJSON["PlayerData"];
            $playerName = $playerDataJSON["PlayerData"]["GameName"];
            $playerTag = $playerDataJSON["PlayerData"]["Tag"];
            $sumid = $playerDataJSON["PlayerData"]["SumID"];
            $puuid = $playerDataJSON["PlayerData"]["PUUID"];
            $rankData = $playerDataJSON["RankData"];
            $masteryData = $playerDataJSON["MasteryData"];
            $matchids = array_keys($playerDataJSON["MatchIDs"]);
        }
    }

    // PRINT SHIT

    if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.avif')){
        $profileIconSrc = '/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.avif');
    }

    $rankOrLevelArray = getRankOrLevel($rankData, $playerData);
    if($rankOrLevelArray["Type"] === "Rank"){ // If user has a rank
    // Additionally print LP count if user is Master+ OR print the rank number (e.g. IV)
        if ($rankOrLevelArray["HighEloLP"] != ""){
            if(fileExistsWithCache('/hdd1/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate-big.avif')){
                $upperPlate = '<img src="/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate-big.avif?version='.md5_file('/hdd1/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate-big.avif').'" width="64" height="24" class="z-[9] absolute pointer-events-none select-none" alt="A plate background image as placeholder for a ranks tier or level">';
                $upperContent = "<div class='font-bold color-[#e8dfcc] absolute mt-[0.3rem] text-xs z-[9]'>".$rankOrLevelArray["HighEloLP"]." LP</div>";
            } 
        } else {
            if(fileExistsWithCache('/hdd1/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate.avif')){
                $upperPlate = '<img src="/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate.avif?version='.md5_file('/hdd1/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate.avif').'" width="30" height="18" class="z-[9] absolute mt-3 pointer-events-none select-none" alt="A plate background image as placeholder for a ranks tier or level">';
                $upperContent = "<div class='font-bold color-[#e8dfcc] absolute mt-[0.85rem] text-xs z-[9]'>".$rankOrLevelArray["RankNumber"]."</div>";
            }
        }
        $lowerPlate = '<img src="/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate.avif?version='.md5_file('/hdd1/clashapp/data/misc/ranks/plates/'.strtolower($rankOrLevelArray["HighestRank"]).'-plate.avif').'" width="38" height="26" class="z-[9] absolute mt-[6.5rem] mr-0.5 pointer-events-none select-none" alt="A plate background image as placeholder for a ranks tier or level">';
        if(fileExistsWithCache('/hdd1/clashapp/data/misc/ranks/wings_'.strtolower($rankOrLevelArray["HighestRank"]).'.avif')){
            $profileBorder = '<img fetchpriority="high" src="/clashapp/data/misc/ranks/wings_'.strtolower($rankOrLevelArray["HighestRank"]).'.avif?version='.md5_file('/hdd1/clashapp/data/misc/ranks/wings_'.strtolower($rankOrLevelArray["HighestRank"]).'.avif').'" width="384" height="384" class="z-[8] twok:max-w-[110%] fullhd:max-w-[148%] twok:top-[-8.25rem] fullhd:top-[-130px] absolute pointer-events-none select-none" style="-webkit-mask-image: linear-gradient(0deg, white 50%, transparent 65%); mask-image: linear-gradient(0deg, white 50%, transparent 65%);" alt="The profile border corresponding to a players rank">';
        }
    } else if($rankOrLevelArray["Type"] === "Level") {
        if(fileExistsWithCache('/hdd1/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.avif')){
            $profileBorder = '<img src="/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.avif?version='.md5_file('/hdd1/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.avif').'" width="190" height="190" class="z-[8] absolute -mt-[2.05rem] pointer-events-none select-none" style="-webkit-mask-image: linear-gradient(0deg, white 75%, transparent 90%); mask-image: linear-gradient(0deg, white 75%, transparent 90%);" alt="The profile border corresponding to a players level">';
        }
    }

    if(isset($playerLanes)){
        if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/'.$playerLanes[0].'.avif')){
            $playerMainRoleSrc = '/clashapp/data/misc/lanes/'.$playerLanes[0].'.avif?version='.md5_file('/hdd1/clashapp/data/misc/lanes/'.$playerLanes[0].'.avif');
        }
        if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/'.$playerLanes[1].'.avif')){
            $playerSecondaryRoleSrc = '/clashapp/data/misc/lanes/'.$playerLanes[1].'.avif?version='.md5_file('/hdd1/clashapp/data/misc/lanes/'.$playerLanes[1].'.avif');
        }
        if(isset($_POST['sumid']) && ($queuedAs != $playerLanes[0] && $queuedAs != $playerLanes[1])){
            if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/'.$queuedAs.'.avif')){
                $roleWarning = '<span class="text-yellow-400 absolute z-40 text-xl -mr-12 font-bold mt-0.5 cursor-help px-1.5" src="/clashapp/data/misc/webp/exclamation-yellow.avif?version='.md5_file('/hdd1/clashapp/data/misc/webp/exclamation-yellow.avif').'" width="16" loading="lazy" @mouseover="exclamation = true" @mouseout="exclamation = false">!</span>
                <div class="bg-black/50 text-white text-center text-xs rounded-lg w-40 whitespace-pre-line py-2 px-3 absolute z-30 -ml-16 twok:bottom-[35.75rem] fullhd:bottom-[34.75rem]" x-show="exclamation" x-transition x-cloak>'.__("This player did not queue on their main position").'
                <svg class="absolute text-black h-2 w-full left-0 ml-14 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                <polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon></svg></div>';
            } 
        }
    }
    
    if($upToDate){
        $matchScore = number_format(array_sum(array_filter($playerDataJSON["MatchIDs"], 'is_numeric')) / count(array_filter($playerDataJSON["MatchIDs"], 'is_numeric')), 2);
    }

    foreach($rankData as $rankQueue){
        if($rankQueue["Queue"] == "RANKED_SOLO_5x5"){ $rankedContent .= "
            <div class='rounded bg-[#0e0f18] my-2.5 p-2'>
            <span class='block text-[0.75rem]'>".__("Ranked Solo/Duo").":</span>
                <span class='text-".strtolower($rankQueue["Tier"])."/100'>".__(ucfirst(strtolower($rankQueue["Tier"]))). " " . $rankQueue["Rank"];
        } else if($rankQueue["Queue"] == "RANKED_FLEX_SR"){ $rankedContent .= "
            <div class='rounded bg-[#0e0f18] my-2.5 p-2'>
                <span class='block text-[0.75rem]'>".__("Ranked Flex").":</span>
                <span class='block text-".strtolower($rankQueue["Tier"])."/100'>".__(ucfirst(strtolower($rankQueue["Tier"]))). " " . $rankQueue["Rank"];
        } 
        if(($rankQueue["Queue"] == "RANKED_SOLO_5x5") || ($rankQueue["Queue"] == "RANKED_FLEX_SR")){
            $rankedContent .= " / " . $rankQueue["LP"] . " ".__("LP")."</span><div class='flex justify-center gap-x-1'><span class='relative block cursor-help'
                onmouseenter='showTooltip(this, \"".__('Winrate')."\", 500, \"top-right\")'
                onmouseleave='hideTooltip(this)'>
                ".__("WR").": </span><span class='inline-block'>" . round((($rankQueue["Wins"]/($rankQueue["Wins"]+$rankQueue["Losses"]))*100),2) . "%</span></div>
                    <span class='text-[0.75rem]'>(".$rankQueue["Wins"]+$rankQueue["Losses"]." ".__("Games").")</span>
                </div>";
        }
    }
    
    $maxVisibleItems = 20;
    if(count($masteryData) >= 1){
        for($i=0; $i<count($masteryData); $i++){
            $masteryContent .= "
            <div class='slider-item flex-none h-full whitespace-nowrap inline-block cursor-grab'>
                <img loading='lazy' src='/clashapp/data/patch/{$currentPatch}/img/champion/{$masteryData[$i]["Filename"]}.avif?version=".md5_file("/hdd1/clashapp/data/patch/{$currentPatch}/img/champion/{$masteryData[$i]["Filename"]}.avif")."' width='64' height='64' class='block relative z-0' alt='A champion icon of the league of legends champion {$masteryData[$i]["Champion"]}'>
                <span class='max-w-[64px] text-ellipsis overflow-hidden whitespace-nowrap block'>{$masteryData[$i]["Champion"]}</span>
                <img loading='lazy' src='/clashapp/data/misc/mastery-{$masteryData[$i]["Lvl"]}.avif?version=".md5_file("/hdd1/clashapp/data/misc/mastery-{$masteryData[$i]["Lvl"]}.avif")."' width='32' height='32' class='relative -top-[5.75rem] -right-11 z-10 "; $masteryContent .= ($masteryData[$i]["Lvl"] == 5) ? 'pb-0.5' : ''; $masteryContent .= "' alt='A mastery hover icon on top of the champion icon in case the player has achieved level 5 or higher'>";
                if (str_replace(',', '', $masteryData[$i]["Points"]) > 999999) {
                    $masteryContent .= "<div class='-mt-7 text-" . getMasteryColor(str_replace(',', '', $masteryData[$i]["Points"])) . "/100'>" . str_replace(",", ".", substr($masteryData[$i]["Points"], 0, 4)) . "m</div>";
                } else {
                    $masteryContent .= "<div class='-mt-7 text-" . getMasteryColor(str_replace(',', '', $masteryData[$i]["Points"])) . "/100'>" . explode(",", $masteryData[$i]["Points"])[0] . "k</div>";
                } $masteryContent .= "
            </div>";
        
            // Stop when we've displayed the max visible items
            if ($i >= $maxVisibleItems - 1) {
                break;
            }
        }
    } else if(empty($masteryData)) {
        $masteryContent = "<img src='/clashapp/data/misc/webp/empty_search.avif?version=".md5_file('/hdd1/clashapp/data/misc/webp/empty_search.avif')."' class='w-20 h-20 mx-auto' alt='A frog emoji with a questionmark'>";
    }

    $smurfProbability = calculateSmurfProbability($playerData, $rankData, $masteryData);
    if ($smurfProbability >= 0.4 && $smurfProbability < 0.6){
        $tagList .= generateTag("Smurf", "bg-tag-yellow", "Low probability", "negative");
    } else if ($smurfProbability >= 0.6 && $smurfProbability <= 0.8){
        $tagList .= generateTag("Smurf", "bg-tag-orange", "Moderately high probability", "negative");
    } else if ($smurfProbability > 0.8){
        $tagList .= generateTag("Smurf", "bg-tag-red", "Very high probability", "negative");
    }
    if(isset($playerDataJSON["Tags"], $playerDataJSON["LanePercentages"])){
        if(isset($playerDataJSON["LanePercentages"][0]) && $playerDataJSON["LanePercentages"][0] != ""){
            if($playerDataJSON["LanePercentages"][0] != "FILL"){
                if(isset($playerDataJSON["Tags"][$playerDataJSON["LanePercentages"][0]])){
                    $tagList .= tagSelector($playerDataJSON["Tags"][$playerDataJSON["LanePercentages"][0]]);
                } else {
                    $tagList .= tagSelector(reset($playerDataJSON["Tags"]));
                }
            } else {
                $tagList .= tagSelector($playerDataJSON["Tags"]["FILL"]);
            }
        }
    }
    
    $matchids_sliced = array_slice($matchids, 0, 15);
    $matchHistoryContent .= "
    <td x-data='{ open: true }' class='single-player-match-history' data-puuid='".$puuid."' data-sumid='".$sumid."'>";
        if($upToDate){
            if(isset($_POST['sumid'])){
                $matchHistoryContent .= printTeamMatchDetailsByPUUID($matchids_sliced, $puuid, $playerDataJSON["MatchIDs"]);
            } else {
                $matchHistoryContent .= printTeamMatchDetailsByPUUID($matchids_sliced, $puuid, $playerDataJSON["MatchIDs"], false);
            }
        }
        $matchHistoryContent .= "
    </td>";


    $responseArray["script"] = $scriptContent;
    $responseArray["recalcSBD"] = $recalculateSuggestedBanData;
    $responseArray["profileIconSrc"] = $profileIconSrc;
    if(isset($upperPlate)) $responseArray["upperPlate"] = $upperPlate;
    if(isset($upperContent)) $responseArray["upperContent"] = $upperContent;
    if(isset($lowerPlate)) $responseArray["lowerPlate"] = $lowerPlate;
    $responseArray["playerLevel"] = $playerData["Level"];
    $responseArray["profileBorder"] = $profileBorder;
    $responseArray["playerName"] = $playerName;
    $responseArray["playerTag"] = $playerTag;
    if(isset($playerMainRoleSrc)) $responseArray["playerMainRoleSrc"] = $playerMainRoleSrc;
    if(isset($playerSecondaryRoleSrc)) $responseArray["playerSecondaryRoleSrc"] = $playerSecondaryRoleSrc;
    if(isset($roleWarning)) $responseArray["roleWarning"] = $roleWarning;
    if(isset($matchScore)) $responseArray["matchScore"] = $matchScore;
    if($rankedContent !== "") $responseArray["rankedContent"] = $rankedContent;
    if($masteryContent !== "") $responseArray["masteryContent"] = $masteryContent;
    if($tagList !== "") $responseArray["tagList"] = $tagList;
    if($matchHistoryContent !== "") $responseArray["matchHistoryContent"] = $matchHistoryContent;
    if(isset($_POST['csrf_token'])) $responseArray["csrfToken"] = $_POST['csrf_token'];

    echo json_encode($responseArray);
}
?>