<?php
include_once('/hdd1/clashapp/functions.php');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if(isset($_POST['sumids'])){
    $matchIDTeamArray = array();
    $masteryDataTeamArray = array();
    $playerLanesTeamArray = array();
    $playerNameTeamArray = explode(',', $_POST['sumids']);
    $playerSumidTeamArray = array_flip($playerNameTeamArray);
    $teamID = $_POST['teamid'];
    $returnString = "";
    global $currentPatch;
    foreach(array_keys($playerSumidTeamArray) as $playerSumid){
        if(!file_exists('/hdd1/clashapp/data/player/'.$playerSumid.'.json')){
            echo "Could not find playerfile for ".$playerSumid;
            return;
        } else {
            $playerDataJSON = json_decode(file_get_contents('/hdd1/clashapp/data/player/'.$playerSumid.'.json'), true);
            foreach(array_keys($playerDataJSON["MatchIDs"]) as $singleMatchID){
                if(!in_array($singleMatchID, $matchIDTeamArray)){
                    $matchIDTeamArray[] = $singleMatchID;
                }
            }
            $masteryDataTeamArray[$playerSumid] = $playerDataJSON["MasteryData"];
            $playerLanesTeamArray[$playerSumid]["Mainrole"] = $playerDataJSON["LanePercentages"][0];
            if(isset($playerDataJSON["LanePercentages"][1])){
                $playerLanesTeamArray[$playerSumid]["Secrole"] = $playerDataJSON["LanePercentages"][1];
            } else {
                $playerLanesTeamArray[$playerSumid]["Secrole"] = "";
            }
            foreach ($playerNameTeamArray as $singleSumid => $index) {
                if($playerDataJSON["PlayerData"]["SumID"] == $singleSumid){
                    $playerNameTeamArray[$singleSumid] = $playerDataJSON["PlayerData"]["Name"];
                }
            }
        }
    }
    // --------------- DEBUGGING ----------------------
    // $returnArray = array();
    // $returnArray["MatchIDs"] = $matchIDTeamArray;
    // $returnArray["PlayerLanes"] = $playerLanesTeamArray;
    // $returnArray["PlayerSumids"] = $playerSumidTeamArray;
    // $returnArray["MasteryData"] = $masteryDataTeamArray;
    // echo json_encode($returnArray);

    $suggestedBanMatchData = getMatchData($matchIDTeamArray);
    $suggestedBanArray = getSuggestedBans(array_keys($playerSumidTeamArray), $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);
    $currentTeamJSON = json_decode(file_get_contents('/hdd1/clashapp/data/teams/'.$teamID.'.json'), true);
    $currentTeamJSON["LastUpdate"] = time();
    $currentTeamJSON["SuggestedBanData"] = $suggestedBanArray;
    $fp = fopen('/hdd1/clashapp/data/teams/'.$teamID.'.json', 'w+');
    fwrite($fp, json_encode($currentTeamJSON));
    fclose($fp);
    // echo json_encode($suggestedBanArray);

    $timer = 0;
    $zIndex = 10;
    foreach($suggestedBanArray as $champname => $banChampion){
            $returnString .= '<div class="suggested-ban-champion inline-block text-center w-16 h-16 opacity-0 relative" style="animation: .5s ease-in-out '.$timer.'s 1 fadeIn; animation-fill-mode: forwards; z-index: '.$zIndex.';" x-data="{ showExplanation: false }">
                <div class="ban-hoverer inline-grid" onclick="addToFile(this.parentElement);" @mouseover="showExplanation=true" @mouseout="showExplanation=false">
                    <img class="cursor-help fullhd:w-12 twok:w-14" width="56" height="56" data-id="' . $banChampion["Filename"] . '" src="/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion["Filename"]) . '.webp" alt="A league of legends champion icon of '.$champname.'"></div>
                <span class="suggested-ban-caption w-16 block">' . $champname . '</span>
                <div class="grid grid-cols-[35%_15%_auto] w-[27rem] bg-black/90 text-white text-center text-xs rounded-lg py-2 absolute ml-16 -mt-[5.5rem] px-3" x-show="showExplanation" x-transition x-cloak @mouseenter="showExplanation = true" @mouseleave="showExplanation = false">
                <div class="py-3 px-2 flex justify-end items-center font-bold border-b-2 border-r-2 border-solid border-dark">Category</div><div class="py-3 px-2 flex justify-center items-center font-bold border-b-2 border-r-2 border-solid border-dark">Addition</div><div class="py-3 px-2 flex justify-start text-left font-bold border-b-2 border-solid border-dark">Explanation</div>';
                if(isset($suggestedBanArray[$champname]["Points"]["Value"])){
                    $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Highest Mastery:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["Points"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$playerSumidTeamArray[$suggestedBanArray[$champname]["Points"]["Cause"]].' achieved a mastery score of '.$suggestedBanArray[$champname]["Points"]["Value"].' on '.$champname.'.</div>';
                }
                if(isset($suggestedBanArray[$champname]["TotalTeamPoints"]["Value"])){
                    $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Total Team Mastery:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["TotalTeamPoints"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">This team has a combined mastery score of '.str_replace(".", ",", $suggestedBanArray[$champname]["TotalTeamPoints"]["Value"]).' on '.$champname.'.</div>';
                }
                if(isset($suggestedBanArray[$champname]["CapablePlayers"]["Value"])){
                    if($suggestedBanArray[$champname]["CapablePlayers"]["Value"] > 1){
                        $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Capable Player:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["CapablePlayers"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$suggestedBanArray[$champname]["CapablePlayers"]["Value"].' summoners of this team are able to play '.$champname.'.</div>';
                    } else {
                        $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Capable Player:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["CapablePlayers"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$suggestedBanArray[$champname]["CapablePlayers"]["Value"].' summoner of this team is able to play '.$champname.'.</div>';
                    }
                }
                if(isset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                    $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Matching Laners:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["MatchingLanersPrio"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">';
                foreach($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"] as $laner){
                    if($laner == reset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                        $returnString .= $playerSumidTeamArray[$laner];
                    } else if($laner == end($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                        $returnString .= " & ".$playerSumidTeamArray[$laner];
                    } else {
                        $returnString .= ", ".$playerSumidTeamArray[$laner];
                    }
                } $returnString .= ' are able to perform with '.$champname.' while matching lanes ('; 
                foreach($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"] as $lane){
                    if($lane == reset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"])){
                        $returnString .= ucfirst(strtolower($lane));
                    } else if($laner == end($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"])){
                        $returnString .= " & ".ucfirst(strtolower($lane));
                    } else {
                        $returnString .= ", ".ucfirst(strtolower($lane));
                    }
                } $returnString .= ').</div>  '; } $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Last Played:</div>
                <div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["LastPlayed"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">The last time someone played '.$champname.' was '.timeDiffToText($suggestedBanArray[$champname]["LastPlayed"]["Value"]).'.</div>';
                if(isset($suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"])){
                    $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">Occurences:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["OccurencesInLastGames"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$champname.' was played ';
                    $returnString .= $suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"] > 1 ? $suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"].' times ' : ' once ';
                    $returnString .= 'in the teams '.$suggestedBanArray[$champname]["OccurencesInLastGames"]["Games"].' unique fetched Flex or Clash games.</div>';
                } 
                if(isset($suggestedBanArray[$champname]["AverageMatchScore"]["Add"])){
                    $returnString .= '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-dark">Average Matchscore:</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["AverageMatchScore"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left">The average matchscore achieved on '.$champname.' is '.$suggestedBanArray[$champname]["AverageMatchScore"]["Value"].'.</div>';
                } $returnString .= '
                <div class="py-3 px-2 flex justify-end items-center font-bold border-solid border-r-2 border-t-2 border-dark">Finalscore:</div><div class="py-3 px-2 flex justify-center items-center underline decoration-double font-bold border-solid border-r-2 border-t-2 border-dark text-base underline-offset-2">'.number_format($suggestedBanArray[$champname]["FinalScore"],2,'.','').'</div><div class="flex justify-end items-end text-gray-600 border-solid border-t-2 border-dark"><a href="/docs" onclick="return false;">&#187; Graphs & Formulas</a></div>
                <svg class="absolute text-black/90 h-4 -ml-4 mt-5 rotate-90" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve"><polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon></svg>
                </div>';
                $returnString .= '</div>';
                $timer += 0.1;
                $zIndex--;
                if($zIndex == 5) $zIndex = 10;
            }

    echo $returnString;
}
?>