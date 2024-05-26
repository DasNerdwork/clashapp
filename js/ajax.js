function generateSinglePlayerData(playerName, playerTag, reload, csrf){
    var xhrSPD = new XMLHttpRequest();
    xhrSPD.open('POST', '/ajax/generatePlayerColumn.php', true);
    xhrSPD.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhrSPD.onreadystatechange = function() {
        if (xhrSPD.readyState === 4 && xhrSPD.status === 200) {
            var response = JSON.parse(xhrSPD.responseText);
            if(response.csrfToken == csrf){
                var scriptContent = response.script;
                var scriptElement = document.createElement('script');
                scriptElement.text = scriptContent;
                document.head.appendChild(scriptElement);
                console.log(response);
                if(response.matchHistoryContent){
                    var cleanContent = sanitizeAndRenderHTML(response.matchHistoryContent);
                    document.getElementById('matchhistory').innerHTML = cleanContent;
                }
            }
        }
    };
    var data = 'name='+playerName+'&tag='+playerTag+'&reload='+reload+'&csrf_token='+csrf;
    xhrSPD.send(data);
}


function generatePlayerColumnData(requestIterator, sumid, teamID, queuedAs, reload, csrf) {
    var xhrPCD = new XMLHttpRequest();
    xhrPCD.open('POST', '/ajax/generatePlayerColumn.php', true);
    xhrPCD.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhrPCD.onreadystatechange = function() {
        if (xhrPCD.readyState === 4 && xhrPCD.status === 200) {
            var response = JSON.parse(xhrPCD.responseText);
            if (response.csrfToken === csrf) {
                var scriptContent = response.script;
                var scriptElement = document.createElement('script');
                scriptElement.text = scriptContent;
                document.head.appendChild(scriptElement);

                document.getElementById('animate-body-' + requestIterator).classList = [];
                document.getElementById('queuerole-' + requestIterator).classList.replace('brightness-100', 'brightness-150');
                let loadingTextElements = document.getElementById('single-player-column-' + requestIterator).querySelectorAll('.text-loading-light');
                loadingTextElements.forEach(function(element) {
                    element.classList.remove('text-loading-light');
                });

                if (!inTeamRanking[sumid]) {
                    inTeamRanking[sumid] = {};
                }

                if (response.profileIconSrc) {
                    let profileIcon = document.getElementById('profileicon-' + requestIterator);
                    profileIcon.removeAttribute('style');
                    profileIcon.src = response.profileIconSrc;
                    if (response.upperPlate && response.upperContent) {
                        profileIcon.insertAdjacentHTML('afterend', response.upperPlate);
                        profileIcon.nextElementSibling.insertAdjacentHTML('afterend', response.upperContent);
                    }
                    if (response.lowerPlate) {
                        profileIcon.nextElementSibling.nextElementSibling.insertAdjacentHTML('afterend', response.lowerPlate);
                    }
                    if (response.profileBorder) {
                        document.querySelectorAll('.profileborder-030').forEach(e => e.remove());
                        if (response.upperPlate || response.upperContent || response.lowerPlate) {
                            profileIcon.nextElementSibling.nextElementSibling.nextElementSibling.insertAdjacentHTML('afterend', response.profileBorder);                
                        } else {
                            profileIcon.nextElementSibling.nextElementSibling.insertAdjacentHTML('afterend', response.profileBorder);                
                        }
                    }
                }

                if (response.playerLevel) {
                    let playerLevelElement = document.querySelector('#single-player-column-' + requestIterator + ' .playerlevel');
                    playerLevelElement.classList.replace('text-loading-light', 'text-[#e8dfcc]');
                    playerLevelElement.innerText = response.playerLevel;
                }

                if (response.playerName) {
                    let playerNameElement = document.getElementById('playername-' + requestIterator);
                    playerNameElement.classList.remove('text-loading-light');
                    playerNameElement.innerText = response.playerName;
                }

                if (response.playerTag) {
                    let playerTagElement = document.getElementById('playertag-' + requestIterator);
                    playerTagElement.classList.replace('bg-loading', 'bg-searchtitle');
                    playerTagElement.classList.replace('text-gray-300', 'text-[#9ea4bd]');
                    playerTagElement.innerText = '#' + response.playerTag;
                }

                if (response.roleWarning) {
                    document.getElementById('queuerole-' + requestIterator).insertAdjacentHTML('afterend', response.roleWarning);
                }

                if (response.playerMainRoleSrc) {
                    let mainRoleElement = document.getElementById('mainrole-' + requestIterator);
                    mainRoleElement.classList.replace('brightness-100', 'brightness-150');
                    mainRoleElement.src = response.playerMainRoleSrc;
                } else {
                    document.getElementById('mainrole-' + requestIterator).remove();
                }

                if (response.playerSecondaryRoleSrc) {
                    let secRoleElement = document.getElementById('secrole-' + requestIterator);
                    secRoleElement.classList.replace('brightness-100', 'brightness-150');
                    secRoleElement.src = response.playerSecondaryRoleSrc;
                } else {
                    document.getElementById('secrole-' + requestIterator).remove();
                }

                if (response.matchScore) {
                    document.getElementById('matchscore-' + requestIterator).innerText = response.matchScore;
                    inTeamRanking[sumid]['Matchscore'] = response.matchScore;
                }

                if (response.rankedContent) {
                    document.getElementById('rankcontent-' + requestIterator).innerHTML = response.rankedContent;
                }

                if (response.highestRank) {
                    inTeamRanking[sumid]['RankedData'] = response.highestRank;
                }

                if (response.masteryContent) {
                    let masteryContent = document.getElementById('masterycontent-' + requestIterator);
                    if (response.masteryContent.includes('slider-item')) {
                        masteryContent.classList.remove('justify-center');
                    } else {
                        masteryContent.classList.remove('overflow-x-scroll');
                        masteryContent.classList.add('mt-6', 'mb-7', 'items-center');
                    }
                    masteryContent.innerHTML = response.masteryContent;
                }

                if (response.tagList) {
                    document.getElementById('taglist-' + requestIterator).innerHTML = response.tagList;
                }

                if (response.matchHistoryContent) {
                    document.getElementById('matchhistory-' + requestIterator).innerHTML = response.matchHistoryContent;
                }

                if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {
                    var xhrCalc = new XMLHttpRequest();
                    xhrCalc.open('POST', '/ajax/calcTeamRanking.php', true);
                    xhrCalc.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                
                    xhrCalc.onreadystatechange = function() {
                        if (xhrCalc.readyState === 4 && xhrCalc.status === 200) {
                            var calcResponse = JSON.parse(xhrCalc.responseText);
                            if (calcResponse.csrfToken === csrf) {
                                if (calcResponse.inTeamRanking) {
                                    var colors = ['#ff0000', '#ffa500', '#008000', '#0000ff', '#800080'];

                                    calcResponse.inTeamRanking.forEach(function(id, index) {
                                        var columnParent = document.querySelector('.single-player-column[data-sumid="' + id + '"]');
                                        if (columnParent) {
                                            var triangleContainer = document.createElement('div');
                                            triangleContainer.style.borderBottom = '40px solid transparent';
                                            triangleContainer.style.borderLeft = '40px solid ' + colors[index % colors.length];
                                            triangleContainer.style.marginBottom = '-40px';

                                            var inTeamRank = document.createElement('div');
                                            inTeamRank.textContent = index + 1;
                                            inTeamRank.classList.add('absolute', 'font-bold');
                                            inTeamRank.style.marginLeft = '-7%';

                                            triangleContainer.appendChild(inTeamRank);
                                            columnParent.parentNode.insertBefore(triangleContainer, columnParent);
                                        }
                                    });
                                }
                            }
                        }
                    };
                    var calcData = 'inTeamRanking=' + JSON.stringify(inTeamRanking) + '&csrf_token=' + csrf;
                    xhrCalc.send(calcData);
                }
            }
        }
    };

    var data = 'iteration=' + requestIterator + '&sumid=' + sumid + '&teamid=' + teamID + '&queuedas=' + queuedAs + '&reload=' + reload + '&csrf_token=' + csrf;
    xhrPCD.send(data);
}
