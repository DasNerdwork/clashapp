<?php session_start(); 
include_once('/hdd1/clashapp/functions.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(isset($_GET['join'])){ // Used for invite codes via ?join=roomCode
    $sanitizedRoom = filter_input(INPUT_GET, 'join', FILTER_SANITIZE_NUMBER_INT);
    if ($sanitizedRoom !== false) {
        setcookie('roomCode', $sanitizedRoom, time()+86400, '/');
        $_COOKIE['roomCode'] = $sanitizedRoom;
        echo '<script>window.location.href = "/minigames";</script>';
    }
}

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
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
$imagePath = "/clashapp/data/patch/13.16.1/img/champion/{$randomChampion['image']['full']}";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['champion_input'];
    $isCorrect = strtolower($userInput) === strtolower($championName);
}



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
    checkAndSetRoomCodeCookie();

    function checkAndSetRoomCodeCookie() {
        const existingRoomCode = getCookie("roomCode");
        if (!existingRoomCode) {
            const newRoomCode = Math.floor(Math.random() * 9000000000) + 1000000000;
            const expirationDate = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours
            setCookie("roomCode", newRoomCode, expirationDate.toUTCString());
        }
    }

    function addChatMessage(name, message){
        const chatContainer = document.getElementById("chatContainer");
        const textMessage = document.createElement("span");
        textMessage.classList.add("text-[#333344]");
        __(message).then(function (result) {
            textMessage.innerText = name + ' ' + message;
            if (chatContainer.children.length > 0) {
                chatContainer.insertBefore(textMessage, chatContainer.children[1]);
            } else {
                chatContainer.appendChild(textMessage);
            }
        });
    }

    const ws = new WebSocket('wss://websocket.dasnerdwork.net/');

    ws.onopen = (event) => { // Do this on client opening the webpage
        if (document.getElementById("highlighter") != null) {
            var name = document.getElementById("highlighter").innerText
        } else {
            var name = "";
        }
        let sendInfo =  {
            roomid: getCookie("roomCode"),
            name: name,
            request: "minigames"
        };
        ws.send(JSON.stringify(sendInfo))
    };

    ws.onmessage = (event) => { // Do this when the WS-Server sends a message to client
        if(Array.from(event.data)[0] == "{"){
            var messageAsJson = JSON.parse(event.data);
            var userList = document.getElementById("userList");
            if (messageAsJson.status == "RoomJoined") {
                addChatMessage(messageAsJson.name, messageAsJson.message);
            } else if (messageAsJson.status == "Message") {
                addChatMessage(messageAsJson.name, messageAsJson.message);
            } else if (messageAsJson.status == "PlayerListUpdate"){
                let playerList = messageAsJson.players;
                const existingUserLis = Array.from(userList.children[1].children);

                // Remove <li> elements that are not in the current player list
                existingUserLis.forEach(existingUserLi => {
                    const liText = existingUserLi.textContent.trim();
                    if (!playerList.includes(liText)) {
                        existingUserLi.remove();
                    }
                });

                // Add new <li> elements for players in the current player list
                playerList.forEach(playerName => {
                    const existingUserLi = Array.from(userList.children[1].children).find(li => li.textContent.trim() === playerName);
                    if (!existingUserLi) {
                        const userName = document.createElement('li');
                        userName.innerText = playerName;
                        userName.classList.add("overflow-hidden", "text-ellipsis", "whitespace-nowrap");
                        userList.children[1].appendChild(userName);
                    }
                });
            }
        }
    }

    ws.onclose = (event) => { // Do this when the WS-Server stops
        clearTimeout(this.pingTimeout);
    }

</script>
<div class="w-full flex justify-center">
    <div class="absolute right-0 bg-dark m-4 p-4 rounded max-w-[256px]" id="userList">
        <h1 class="font-bold"><?= __("Users inside this room:") ?></h1>
        <ol class="list-decimal list-inside"></ol>
    </div>
    <div class="flex justify-center gap-x-8 mt-40 bg-dark rounded w-fit p-4">
        <div class="text-center mb-4 flex justify-center flex-col items-center w-72">
            <h1 class="text-3xl font-bold mb-4"><?= __("Pixel Guesser") ?></h1>
            <canvas id="pixelatedCanvas" width="256" height="256"></canvas>
            <img id="fullImage" src="<?= $imagePath ?>" alt="Full Image" width="256" height="256" class="mt-[-6.5rem]">
            <span class="text-xl h-4 mt-4 animate-fadeIn opacity-0" id="championName"></span>
            <form method="post" class="text-center flex mt-8" id="championForm" onsubmit="checkChamp(); return false;" autocomplete="off">
                <input  type="text" 
                        name="champion_input"
                        class="autofill:text-black text-black border border-2 border-solid border-white p-2 focus:border focus:border-2 focus:border-solid focus:border-white <?php if (isset($isCorrect)) echo $isCorrect ? 'correct-border' : 'incorrect-border'; ?>"
                        onkeyup="handleInputKeyUp(event)">
                <div id="suggestions"></div>
                <button type="submit" class="bg-tag-navy text-white px-4 py-2 border-2 border-solid border-tag-navy">&#10148;</button>
            </form>
            <div class="flex justify-center items-center mt-4">
                <span class="text-xl"><?= __("Difficulty: ") ?></span>
                <select id="difficulty-selector" 
                        class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none"
                        onchange="setCookie('pixelChamp', this.value); this.disabled = true; location.reload();">
                    <option value="easy" <?= (!isset($_COOKIE["pixelChamp"]) || $_COOKIE["pixelChamp"] == "easy") ? "selected disabled hidden" : ""; ?>><?= __("Easy") ?></option>
                    <option value="medium" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "medium") ? "selected disabled hidden" : ""; ?>><?= __("Medium") ?></option>
                    <option value="hard" <?= (isset($_COOKIE["pixelChamp"]) && $_COOKIE["pixelChamp"] == "hard") ? "selected disabled hidden" : ""; ?>><?= __("Hard") ?></option>
                </select>
            </div>
        </div>
        <div class="flex items-center flex-col justify-start w-96 gap-y-4">
            <span class="text-xl"><?= __("Bonus Points: ") ?></span>
            <div id="bonus-bar" class="bonus-bar"></div>
            <div id='chatContainer' class='bg-darker w-full h-64 p-2 flex flex-col-reverse overflow-auto twok:text-base fullhd:text-sm'></div>
        </div>
    </div>
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

    switch (getCookie("pixelChamp")) {
        case "easy":
            pixelateImage("<?= $imagePath ?>", 25); // Adjust blockSize as needed
            break;
        case "medium":
            pixelateImage("<?= $imagePath ?>", 33); // Adjust blockSize as needed
            break;
        case "hard":
            pixelateImage("<?= $imagePath ?>", 50); // Adjust blockSize as needed
            break;
        default:
            pixelateImage("<?= $imagePath ?>", 25); // Adjust blockSize as needed
            break;
    }

    userInput.select();

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

    function displaySuggestions(championList) {
        suggestionsContainer.innerHTML = "";
        const maxSuggestions = 5; // Limit the number of displayed suggestions

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
            suggestion.textContent = championList[i]; // Using the original champion name from championKeys
            suggestion.classList.add("suggestion");
            suggestion.addEventListener("click", () => {
                userInput.value = championList[i]; // Populate the input with the selected suggestion
                clearSuggestions();
            });

            suggestionsContainer.appendChild(suggestion);
        }
    }

    function clearSuggestions() {
        suggestionsContainer.innerHTML = "";
    }

    function handleInputKeyUp(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            checkChamp(); // Trigger the checkChamp function on Enter key press
        } else {
            updateInputBorder(false); // Update the input border when any key is released
        }
    }

    function pixelateImage(imagePath, blockSize) {
        const canvas = document.getElementById("pixelatedCanvas");
        const context = canvas.getContext("2d");
        const image = new Image();

        image.onload = function() {
            const scaledWidth = canvas.width / blockSize;
            const scaledHeight = canvas.height / blockSize;
            context.drawImage(image, 0, 0, scaledWidth, scaledHeight);
            context.mozImageSmoothingEnabled = false;
            context.webkitImageSmoothingEnabled = false;
            context.imageSmoothingEnabled = false;
            context.drawImage(canvas, 0, 0, scaledWidth, scaledHeight, 0, 0, canvas.width, canvas.height);
        };

        image.src = imagePath;
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

    function checkChamp() {
        const userInput = document.querySelector("input[name='champion_input']");
        const championName = "<?= strtolower($championName) ?>".replace(/'/g, '');
        const userAnswer = userInput.value.toLowerCase().replace(/'/g, '');

        const isCorrect = userAnswer === championName;
        updateInputBorder(isCorrect);

        if (isCorrect) {
            const startTime = performance.now();
            const champName = document.getElementById("championName")
            champName.innerText = "<?= __("Correct Answer: ").$championName ?>";

            function revealFullImage(timestamp) {
                const elapsedTime = timestamp - startTime;
                const progress = Math.min(elapsedTime / 1000, 1);

                document.getElementById("fullImage").style.opacity = progress;

                if (progress < 1) {
                    requestAnimationFrame(revealFullImage);
                } else {
                    champName.classList.replace("opacity-0", "opacity-100")
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            }

            requestAnimationFrame(revealFullImage);
        }
    }
    </script>
<?php
include('/hdd1/clashapp/templates/footer.php');
?>