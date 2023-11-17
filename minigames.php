<?php if (session_status() === PHP_SESSION_NONE) session_start(); 
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/clash-db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// print_r($_SESSION);

if(isset($_GET['join'])){ // Used for invite codes via ?join=roomCode
    $sanitizedRoom = filter_input(INPUT_GET, 'join', FILTER_SANITIZE_NUMBER_INT);
    if ($sanitizedRoom !== false) {
        setcookie('roomCode', $sanitizedRoom, time()+86400, '/');
        $_COOKIE['roomCode'] = $sanitizedRoom;
        echo '<script>window.location.href = "/minigames";</script>';
    }
}

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = true, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

$championData = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/champion.json'), true);
$championKeys = array(); // Initialize an empty array
foreach ($championData['data'] as $key => $champion) {
    $originalName = $champion['name'];
    $championKeys[$originalName] = $key;
}
$randomChampionKey = $championKeys[array_rand($championKeys)];
$randomChampion = $championData['data'][$randomChampionKey];
$championName = $randomChampion['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['champion_input'];
    $isCorrect = strtolower($userInput) === strtolower($championName);
}
$db = new DB();
?>
<style>
    .correct-border {
        border: 2px solid green !important;
    }
    .incorrect-border {
        border: 2px solid red !important;
    }
    #imageContainer {
        position: relative;
    }
    #fullImage {
        position: absolute;
        opacity: 0;
        transition: opacity 1s ease;
    }
    .animate-border-color {
        transition: border-color 0.5s ease-out;
    }
    #championName {
        transition: opacity 0.5s ease-in;
    }
    #suggestions {
        position: absolute;
        margin-top: 2.8rem;
        width: 13.45rem;
        color: #000;
        text-align: left;
    }
    .suggestion {
        cursor: pointer;
        padding: 5px;
        border: 1px solid #ccc;
        background-color: #eee;
        margin-top: -1px;
    }
    .suggestion:hover {
        background-color: #ccc;
    }
    .bonus-bar {
        width: 100%;
        height: 1.5rem;
        background-color: green;
        animation: decreaseBonus 10s linear forwards;
        transform-origin: left center;
    }

    @keyframes decreaseBonus {
        to {
            /* More performant than animating `width` */
            transform: scaleX(0);
        }
    }

    .bonus-bar.stopped {
        animation: none;
    }
</style>
<script>
    let championName = "";
    let fullImageSource = "";
    var ownUsername = "";
    let isFormSubmissionBlocked = false;
    checkAndSetRoomCodeCookie();

    function checkAndSetRoomCodeCookie() {
        const existingRoomCode = getCookie("roomCode");
        if (!existingRoomCode) {
            const newRoomCode = Math.floor(Math.random() * 9000000000) + 1000000000;
            const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
            setCookie("roomCode", newRoomCode, expirationDate.toUTCString());
        }
    }

    function addChatMessage(name, message, color, arg1 = ''){
        arg1 = arg1 || "";
        const chatContainer = document.getElementById("chatContainer");
        const textMessage = document.createElement("span");
        // textMessage.classList.add("text-[#333344]");
        __(message).then(function (result) {
            textMessage.innerHTML = "<span class='text-"+color+"/100'>"+name+"</span> "+result.replace("%1", arg1);
            if (chatContainer.children.length > 0) {
                chatContainer.insertBefore(textMessage, chatContainer.children[0]);
            } else {
                chatContainer.appendChild(textMessage);
            }
        });
    }

    const ws = new WebSocket('wss://websocket.dasnerdwork.net/');

    ws.onopen = (event) => { // Do this on client opening the webpage
        let highlighterElem = document.getElementById("highlighter");
        if (highlighterElem != null) {
            if(highlighterElem.dataset.username){
                if(highlighterElem.dataset.username != ""){
                    var name = highlighterElem.dataset.username;
                } else {
                    var name = document.getElementById("highlighter").innerText
                }
            } else {
                var name = document.getElementById("highlighter").innerText
            }
        } else {
            var name = "";
        }
        const pixelChampDifficulty = getCookie("pixelChamp") || "easy";
        let sendInfo =  {
            roomid: getCookie("roomCode"),
            name: name,
            difficulty: pixelChampDifficulty,
            request: "minigames",
            action: "generate"
        };
        ws.send(JSON.stringify(sendInfo))
    };

    ws.onmessage = (event) => { // Do this when the WS-Server sends a message to client
        if(Array.from(event.data)[0] == "{"){
            var messageAsJson = JSON.parse(event.data);
            var userList = document.getElementById("userList");
            if (messageAsJson.status == "RoomJoined") {
                addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color);
                ownUsername = messageAsJson.name;
            } else if (messageAsJson.status == "Message") {
                if(messageAsJson.answer){
                    const username = "<?= isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : ''; ?>";
                    if(username != ""){
                        postAjax('../ajax/pixelGuesser.php', { username: username, points: (100+parseInt(messageAsJson.bonuspoints, 10)) }, function(responseText) {
                            if (responseText === 'success') {
                                addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color, messageAsJson.answer);
                                if(ownUsername === messageAsJson.name){
                                    let points = document.getElementById('gamePoints');
                                    points.innerHTML = parseInt(points.innerHTML, 10)+100+parseInt(messageAsJson.bonuspoints, 10);
                                    // Create the pointIndicator element
                                    const pointIndicator = document.createElement('span');
                                    pointIndicator.className = 'text-sm opacity-0 absolute text-[#00FF00] animate-moveUpAndFadeOut';
                                    pointIndicator.textContent = '  +'+(100+parseInt(messageAsJson.bonuspoints, 10));
                                    points.insertAdjacentElement('afterEnd', pointIndicator);
                                    pointIndicator.addEventListener('animationend', function () {
                                        // points.parentElement.removeChild(pointIndicator);
                                    });
                                }
                            } else {
                                console.error('Error adding points');
                            }
                        });
                    } else {
                        addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color, messageAsJson.answer);
                    }
                    // Dynamically update leaderboard points
                    let leaderboardListItems = document.getElementById('leaderboardList').getElementsByTagName('li');
                    for (let i = 0; i < leaderboardListItems.length; i++) {
                        let playersNameDiv = leaderboardListItems[i].getElementsByTagName('div')[0];
                        if (playersNameDiv.textContent.includes(messageAsJson.name)) {
                            let playerPointsSpan = leaderboardListItems[i].getElementsByTagName('span')[0];
                            let currentPoints = parseInt(playerPointsSpan.textContent.match(/\d+/)[0]);
                            playerPointsSpan.textContent = "(" + (currentPoints + 100 + parseInt(messageAsJson.bonuspoints, 10)) + ")";
                            break; // Once the player is found, exit the loop
                        }
                    }
                    // Unveil image and text
                    const startTime = performance.now();
                    const champName = document.getElementById("championName");
                    const fullImage = document.getElementById("fullImage");
                    champName.innerText = "<?= __("It was: ") ?>"+atob(championName);
                    fullImage.src = atob(fullImageSource);

                    function revealFullImage(timestamp) {
                        const elapsedTime = timestamp - startTime;
                        const progress = Math.min(elapsedTime / 1000, 1);
                        document.getElementById("fullImage").style.opacity = progress;

                        if (progress < 1) {
                            requestAnimationFrame(revealFullImage);
                        } else {
                            champName.style.opacity = 1;
                        }
                    }

                    requestAnimationFrame(revealFullImage);
                } else {
                    addChatMessage(messageAsJson.name, messageAsJson.message, messageAsJson.color);
                }
            } else if (messageAsJson.status == "PlayerListUpdate") {
                let playerList = messageAsJson.players;
                const existingUserLis = Array.from(userList.children[1].children);

                // Remove <li> elements that are not in the current player list
                existingUserLis.forEach(existingUserLi => {
                    const liText = existingUserLi.textContent.trim();
                    if (!playerList.includes(liText) && !liText.includes(ownUsername)) {
                    existingUserLi.remove();
                    }
                });

                // Find and remove the existing (You) entry for your name
                const existingYouLi = existingUserLis.find(li => li.textContent.includes(ownUsername));
                if (existingYouLi) {
                    existingYouLi.remove();
                }

                // Add new <li> elements for players in the current player list
                playerList.forEach(playerName => {
                    const existingUserLi = existingUserLis.find(li => li.textContent.trim() === playerName);
                    if (!existingUserLi) {
                    const userName = document.createElement('li');
                    userName.innerText = playerName;

                    if (playerName === ownUsername) {
                        userName.innerHTML = "<span class='text-"+messageAsJson.colors[playerName]+"/100'>"+playerName+"</span> " + " (<?= __("You") ?>)";
                        userName.classList.add("overflow-hidden", "text-ellipsis", "whitespace-nowrap", "text-white", "font-bold");
                        userList.children[1].insertBefore(userName, userList.children[1].firstChild);
                    } else {
                        userName.innerHTML = "<span class='text-"+messageAsJson.colors[playerName]+"/100'>"+playerName+"</span>";
                        userName.classList.add("overflow-hidden", "text-ellipsis", "whitespace-nowrap", "text-gray");
                        userList.children[1].appendChild(userName);
                    }
                    }
                });
                }
                else if (messageAsJson.status === "PixelateAndGenerate") {
                const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
                pixelationDifficulty = messageAsJson.pixelationDifficulty;
                imagePath = messageAsJson.imagePath;
                difficultySelector.value = pixelationDifficulty;
                setCookie("pixelChamp", pixelationDifficulty, expirationDate.toUTCString());
                const options = difficultySelector.getElementsByTagName("option");
                for (const option of options) {
                    if(option.value === pixelationDifficulty){
                        option.setAttribute("hidden", "");
                    } else {
                        option.removeAttribute("hidden");
                    }
                }
                resetBonusBar();

                // Save variables
                fullImageSource = imagePath;
                championName = messageAsJson.championName;

                switch (pixelationDifficulty) {
                    case "easy":
                        pixelateImage(imagePath, 25); // Adjust blockSize as needed
                        break;
                    case "medium":
                        pixelateImage(imagePath, 33); // Adjust blockSize as needed
                        break;
                    case "hard":
                        pixelateImage(imagePath, 50); // Adjust blockSize as needed
                        break;
                    default:
                        pixelateImage(imagePath, 25); // Adjust blockSize as needed
                        break;
                }
            } else if (messageAsJson.status === "PixelateAndGenerateNew") {
                setTimeout(() => {
                    userInput.value = "";
                    userInput.select();
                    const champName = document.getElementById("championName");
                    const fullImage = document.getElementById("fullImage");
                    const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
                    champName.innerText = "";
                    fullImage.style.transition = "none"
                    fullImage.style.opacity = 0;
                    fullImage.src = "";
                    pixelationDifficulty = messageAsJson.pixelationDifficulty;
                    imagePath = messageAsJson.imagePath;
                    if (userInput.classList.contains("correct-border")) {
                        userInput.classList.remove("correct-border");
                    }
                    difficultySelector.value = pixelationDifficulty;
                    setCookie("pixelChamp", pixelationDifficulty, expirationDate.toUTCString());
                    const options = difficultySelector.getElementsByTagName("option");
                    for (const option of options) {
                        if(option.value === pixelationDifficulty){
                            option.setAttribute("hidden", "");
                        } else {
                            option.removeAttribute("hidden");
                        }
                    }
                    resetBonusBar();

                    // Save variables
                    fullImageSource = imagePath;
                    championName = messageAsJson.championName;

                    switch (pixelationDifficulty) {
                        case "easy":
                            pixelateImage(imagePath, 25); // Adjust blockSize as needed
                            break;
                        case "medium":
                            pixelateImage(imagePath, 33); // Adjust blockSize as needed
                            break;
                        case "hard":
                            pixelateImage(imagePath, 50); // Adjust blockSize as needed
                            break;
                        default:
                            pixelateImage(imagePath, 25); // Adjust blockSize as needed
                            break;
                    }
                    isFormSubmissionBlocked = false; // Unblock form submission
                }, 4000);
            }
        }
    }

    ws.onclose = (event) => { // Do this when the WS-Server stops
        clearTimeout(this.pingTimeout);
    }

</script>
<div class="w-full flex justify-center">
    <div class="absolute right-0 max-w-[256px] flex flex-col <?= __("invLink") ?>">
        <button 
            id="inviteLink"
            class="bg-tag-navy flex justify-center m-4 p-4 rounded cursor-pointer hover:opacity-80 active:opacity-70"
            onclick="copyInviteLink(this, '<?= __('Copied') ?>', 0, 'top-right', '-mt-8 animate-moveUpAndFadeOut');">

            <h1 class="font-bold text-center">&#128279; <?= __("Copy Invite Link") ?></h1>
        </button>
        <div id="userList" class="bg-dark mx-4 mb-4 p-4 rounded">
            <h1 class="font-bold"><?= __("Users inside this room:") ?></h1>
            <ol class="list-decimal list-inside"></ol>
        </div>
    </div>
    <div class="absolute left-0 max-w-[256px] flex flex-col">
        <div id="leaderboard" class="bg-dark m-4 p-4 rounded">
            <h1 class="font-bold text-xl pb-2 text-center"><?= __("Leaderboard:") ?></h1>
            <ol class="list-decimal list-inside" id="leaderboardList">
                <?php 
                $leaderboard = $db->getTopPlayers();
                if ($leaderboard !== false) {
                    foreach ($leaderboard as $index => $player) {
                        echo "<li class='pt-2 pb-1 border-b border-dashed border-[#21222c] flex items-center'>";
                        echo "<div class='w-40 truncate'>" . $index+1 . ". " .  $player["username"] . "</div>";
                        echo "<span class='ml-auto'>(" . $player["points"] . ")</span>";
                        echo "</li>";
                    }
                }
                ?>
            </ol>
        </div>
    </div>
    <div class="flex justify-center gap-x-8 mt-40 bg-dark rounded w-fit p-4">
        <div id="canvasContainer" class="text-center mb-4 flex justify-center flex-col items-center w-72">
            <h1 class="text-3xl font-bold mb-4"><?= __("Pixel Guesser") ?></h1>
            <canvas id="pixelatedCanvas" width="256" height="256"></canvas>
            <img id="fullImage" src="" alt="Full Image" width="256" height="256" class="mt-[-6.5rem]">
            <span class="text-xl h-4 mt-4 animate-fadeIn opacity-0" id="championName"></span>
            <form method="post" class="text-center flex mt-8" id="championForm" onsubmit="checkChamp(); return false;" autocomplete="off">
                <input  type="text" 
                        name="champion_input"
                        placeholder="Aatrox, Ahri, etc."
                        class="autofill:text-black text-black border border-2 border-solid border-white p-2 focus:border focus:border-2 focus:border-solid focus:border-white <?php if (isset($isCorrect)) echo $isCorrect ? 'correct-border' : 'incorrect-border'; ?>"
                        onkeydown="handleInputKeyDown(event)">
                <div id="suggestions"></div>
                <button type="submit" class="bg-tag-navy text-white px-4 py-2 border-2 border-solid border-tag-navy">&#10148;</button>
            </form>
            <div class="flex justify-center items-center mt-4">
                <span class="text-xl"><?= __("Difficulty: ") ?></span>
                <select id="difficulty-selector" 
                        class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none"
                        onchange="setCookie('pixelChamp', this.value); this.disabled = true; location.reload();">
                    <option value="easy" <?= (!isset($_COOKIE["pixelChamp"]) || $_COOKIE["pixelChamp"] == "easy") ? "hidden" : ""; ?>><?= __("Easy") ?></option>
                    <option value="medium" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "medium") ? "hidden" : ""; ?>><?= __("Medium") ?></option>
                    <option value="hard" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "hard") ? "hidden" : ""; ?>><?= __("Hard") ?></option>
                </select>
            </div>
        </div>
        <div class="flex items-center flex-col justify-start w-[420px] gap-y-4">
            <span class="text-xl"><?= __("Bonus Points: ") ?></span>
            <div id="bonus-bar" class="bonus-bar"></div>
            <div id='chatContainer' class='bg-darker w-full max-h-80 h-full p-2 flex flex-col-reverse overflow-auto twok:text-base fullhd:text-sm'></div>
            <?php 
            if(isset($_SESSION['user']['email'], $_SESSION['user']['username'])){ 
                echo '<div class="text-xl">'.__("Points: "); 
                $points = $db->getPoints($_SESSION['user']['username']); 
                if($points !== false) { 
                    echo "<span id='gamePoints' class='font-bold'>".$points."</span>"; 
                }
                echo '</div>';
            } else {
                echo "<div class='text-xl cursor-help' x-data=\"{ showNotice: false }\" x-cloak @mouseover='showNotice = true' @mouseout='showNotice = false'>".__('Points: ')."???
                        <div class='flex justify-center gap-x-0 -mt-8' x-cloak>
                            <span class='text-sm absolute backdrop-blur-2xl bg-black/80 p-2 rounded' x-show='showNotice' x-transition>".sprintf(__("Please %slogin%s or %sregister%s to see and save your score"), "<a href='/login' class='underline'>", "</a>", "<a href='/register' class='underline'>", "</a>")."</span>
                        </div></div>";


            }
            ?>
        </div>
    </div>
    <?php 
    // if(isset($_SESSION['user']['email'])){ echo'
    // <div class="flex items-center flex-col justify-start w-96 gap-y-4">
    // <span>Points: '; 
    // if(isset($_SESSION['user']['username'])){
    //     $points = $db->getPoints($_SESSION['user']['username']);
    //     if($points !== false) {
    //         echo $points;
    //     }
    // }
    // echo '</span>
    // </div>
    // ';
    //} 
    ?>
</div>

    <script>
    const championKeys = <?= json_encode($championKeys) ?>;
    const suggestionsContainer = document.getElementById("suggestions");
    const userInput = document.querySelector("input[name='champion_input']");

    userInput.addEventListener("input", handleInput);
    userInput.addEventListener("focus", handleInput);

    document.addEventListener("click", function(event) {
        if (!userInput.contains(event.target) && !suggestionsContainer.contains(event.target)) {
            clearSuggestions();
        }
    });

    function handleInput(event) {
        const inputText = event.target.value.toLowerCase();

        if (inputText.length >= 1) {
            const matchingChampionsNames = Object.keys(championKeys).filter(championName =>
                championName.toLowerCase().includes(inputText)
            );

            displaySuggestions(matchingChampionsNames);
        } else {
            clearSuggestions();
        }
    }

    let highlightedIndex = -1;
    let arrowKeyPressed = false;
    let emptySuggestions = true;

    function displaySuggestions(championList) {
        suggestionsContainer.innerHTML = "";
        arrowKeyPressed = false;
        emptySuggestions = false;
        const maxSuggestions = 5;

        suggestionsContainer.addEventListener("mouseenter", () => {
            arrowKeyPressed = false;
        });

        const sortedChampionList = championList.sort((a, b) => {
            const aStartsWithInput = a.toLowerCase().startsWith(userInput.value.toLowerCase());
            const bStartsWithInput = b.toLowerCase().startsWith(userInput.value.toLowerCase());

            if (aStartsWithInput && !bStartsWithInput) {
                return -1;
            } else if (!aStartsWithInput && bStartsWithInput) {
                return 1;
            } else {
                return a.localeCompare(b);
            }
        });

        for (let i = 0; i < Math.min(championList.length, maxSuggestions); i++) {
            const suggestion = document.createElement("div");
            suggestion.textContent = championList[i];
            suggestion.classList.add("suggestion");

            suggestion.addEventListener("mouseenter", () => {
                removeHighlight();
            });

            suggestion.addEventListener("click", () => {
                userInput.value = championList[i];
                clearSuggestions();
            });

            suggestionsContainer.appendChild(suggestion);

            if(championList.length == 1){
                highlightSuggestion(0);
            }
        }
    }

    function highlightSuggestion(index) {
        removeHighlight();
        if (index >= 0 && index < suggestionsContainer.children.length) {
            highlightedIndex = index;
            suggestionsContainer.children[index].style.backgroundColor = "#ccc";
        }
    }

    function removeHighlight() {
        if (highlightedIndex !== -1) {
            suggestionsContainer.children[highlightedIndex].style.backgroundColor = "";
            highlightedIndex = -1;
        }
    }

    function handleInputKeyDown(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            if (highlightedIndex !== -1 && !emptySuggestions) {
                userInput.value = suggestionsContainer.children[highlightedIndex].textContent;
                clearSuggestions();
            } else {
                checkChamp();
            }
        } else if (event.key === "ArrowDown" || event.key === "Tab") {
            event.preventDefault();
            if (!arrowKeyPressed) {
                arrowKeyPressed = true;
                highlightSuggestion(0); // Highlight the first suggestion on the first arrow key press
            } else if (highlightedIndex < suggestionsContainer.children.length - 1) {
                highlightSuggestion(highlightedIndex + 1);
            }
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (!arrowKeyPressed) {
                arrowKeyPressed = true;
                highlightSuggestion(suggestionsContainer.children.length - 1); // Highlight the last suggestion on the first arrow key press
            } else if (highlightedIndex > 0) {
                highlightSuggestion(highlightedIndex - 1);
            }
        }
    }

    // Add this event listener to start highlighting on the first arrow key press
    document.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp") {
            event.preventDefault();
            if (highlightedIndex === -1) {
                highlightSuggestion(0);
            }
        }
    });

    function clearSuggestions() {
        suggestionsContainer.innerHTML = "";
        arrowKeyPressed = false;
        emptySuggestions = true;
    }

    function pixelateImage(imagePath, blockSize) {
    // Remove the old canvas if it exists
    const oldCanvas = document.getElementById("pixelatedCanvas");
    if (oldCanvas) {
        oldCanvas.parentNode.removeChild(oldCanvas);
    }

    // Create a new canvas element
    const canvas = document.createElement("canvas");
    canvas.id = "pixelatedCanvas";
    canvas.width = 256; // Set the desired width
    canvas.height = 256; // Set the desired height
    document.getElementById("canvasContainer").insertBefore(canvas, document.getElementById("canvasContainer").children[1]); // Append the new canvas to the body

    const context = canvas.getContext("2d");
    const image = new Image();

    image.onload = function() {
        const scaledWidth = canvas.width / blockSize;
        const scaledHeight = canvas.height / blockSize;
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, scaledWidth, scaledHeight);
        context.mozImageSmoothingEnabled = false;
        context.webkitImageSmoothingEnabled = false;
        context.imageSmoothingEnabled = false;
        context.drawImage(canvas, 0, 0, scaledWidth, scaledHeight, 0, 0, canvas.width, canvas.height);
    };

    image.src = atob(imagePath);
    }

    function updateInputBorder(isCorrect) {
        const userInput = document.querySelector("input[name='champion_input']");

        if (isCorrect) {
            userInput.classList.remove("incorrect-border", "animate-border-color");
            userInput.classList.add("correct-border");
        } else {
            userInput.classList.remove("correct-border");
            userInput.classList.add("incorrect-border");
            setTimeout(() => {
                userInput.classList.add("animate-border-color");
                userInput.classList.remove("incorrect-border");
                setTimeout(() => {
                    userInput.classList.remove("animate-border-color");
                }, 500);
            }, 500); // Remove incorrect-border class immediately without fade out
        }
    }

    function calculateBonusPoints() {
        const bonusBar = document.getElementById('bonus-bar');
        const scaleXValue = window.getComputedStyle(bonusBar).transform.split(',')[0].split('(')[1];
        const scaleXPercentage = parseFloat(scaleXValue) * 100;

        // Calculate bonus points proportional to the scaleX percentage
        const bonusPoints = Math.floor(scaleXPercentage);

        return Math.min(bonusPoints, 100); // Cap bonusPoints at 100
    }

    function resetBonusBar() {
        const bonusBar = document.getElementById('bonus-bar');

        // Add the stopped class to stop the animation
        bonusBar.classList.add('stopped');

        // Remove the animation class to reset the animation
        bonusBar.classList.remove('decreaseBonus');

        // Force a reflow to ensure the animation class is removed before reapplying it
        void bonusBar.offsetWidth;

        // Remove the stopped class to restart the animation from full width
        bonusBar.classList.remove('stopped');

        // Reapply the animation class to restart the animation
        bonusBar.classList.add('decreaseBonus');
    }

    function checkChamp() {
        if (isFormSubmissionBlocked) {
            return; // Don't process the form if submission is blocked
        }
        const userInput = document.querySelector("input[name='champion_input']");
        const correctAnswer = championName;
        const userAnswer = userInput.value.toLowerCase().replace(/'/g, '');

        const isCorrect = userAnswer === atob(correctAnswer).toLowerCase().replace(/'/g, '');
        updateInputBorder(isCorrect);

        if (isCorrect) {
            // Send a message to the WebSocket server indicating the correct answer
            isFormSubmissionBlocked = true;
            let correctAnswerMessage =  {
                roomid: getCookie("roomCode"),
                name: name,
                difficulty: getCookie("pixelChamp"),
                request: "correctAnswer",
                answer: atob(correctAnswer),
                bonuspoints: calculateBonusPoints()
            };
            ws.send(JSON.stringify(correctAnswerMessage))

            const startTime = performance.now();
            const champName = document.getElementById("championName");
            const fullImage = document.getElementById("fullImage");
            champName.innerText = "<?= __("It was: ") ?>"+atob(championName);
            fullImage.src = atob(fullImageSource);

            function revealFullImage(timestamp) {
                const elapsedTime = timestamp - startTime;
                const progress = Math.min(elapsedTime / 1000, 1);
                document.getElementById("fullImage").style.opacity = progress;

                if (progress < 1) {
                    requestAnimationFrame(revealFullImage);
                } else {
                    champName.style.opacity = 1;
                }
            }

            requestAnimationFrame(revealFullImage);
        }
    }

        // Select element for difficulty
        const difficultySelector = document.getElementById('difficulty-selector');

        // Event listener for select menu change
        difficultySelector.addEventListener('change', function () {
            const newDifficulty = this.value;

            // Construct the message to send
            const message = {
                request: 'changeDifficulty',
                roomid: getCookie('roomCode'),
                difficulty: newDifficulty
            };

            // Send the message to the websocket server
            ws.send(JSON.stringify(message));
        });
    </script>
<?php
include('/hdd1/clashapp/templates/footer.php');
?>