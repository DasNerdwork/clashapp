</div>
<footer class="full-footer" id="full-footer">
    <div class="legal-footer" id="colophon" itemtype="https://schema.org/WPFooter" itemscope="itemscope" itemid="#colophon">
        <div class="clash-footer"><p style="text-align: center;"> Copyright © 2022 DasNerdwork.net | <a class="clash-footer-link" href="https://dasnerdwork.net/impressum">Impressum</a> &amp; <a class="clash-footer-link" href="https://dasnerdwork.net/Datenschutzerklaerung">Datenschutzerklärung</a></p>
    </div>
    <div>
        <div class="clash-footer">DasNerdwork.net isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing Riot Games properties.
        <br>Riot Games, and all associated properties are trademarks or registered trademarks of Riot Games, Inc.</div>
    </div>
    <div style="position: relative; bottom: 20px; right: 10px; float: right; opacity: 0.15; font-size: 10px;" id="version">
        <?php 
            exec('cd /hdd2/clashapp && git rev-list --all --count', $output); 
            $createDate = new DateTime("2022-01-28"); // 28.01.2022
            $today = new DateTime("today");
            $difference = $createDate->diff($today);
            echo "v0.".sprintf("%03d", $output[0]).".".($difference->y+1).".".($difference->m+($difference->y*12)).".".sprintf("%04d", $difference->days).
            "<!-- v0.commitCount.yearsSinceCreate.monthsSinceCreate.daysSinceCreate -->";
        ?>
    </div>
</footer>