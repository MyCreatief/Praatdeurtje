<?php
/**
 * Plugin Name: Praatdeurtje — dagelijkse slaapverhaaltjes (video + blog)
 * Description: Genereert elke dag een geïllustreerd slaapverhaaltje met Mosje in het Praatdeurtjesbos. Leest een vaste wereld-/karakterbijbel (pd_canon) zodat alles consistent blijft en meegroeit. gpt-4o verhaal (peuter/kleuter-taalniveau) -> 5 gpt-image-1 illustraties (echte JPEG) -> ElevenLabs voorleesstem -> Shotstack-video (16:9, ASYNC: insturen + later ophalen) -> blogpost in "Verhalen" -> YouTube (Gemaakt voor kinderen) + afspeellijst. Ruimt zware bestanden op na publicatie. State op blog 5; gedeelde keys op blog 7.
 * Version: 0.38.0
 * Changelog: 0.38.0 - langere verhalen (2026-06-22): 6 scènes (was 5) en target-woorden dynamisch uit $target (defaults 320 in arc-mode via pd_arc_target_words). Validatie accepteert 5–7 scènes. Admin-formulier toont 7 invulvelden.
 * 0.37.3 - rustvideo-lock (2026-06-22): pd_rustvideo_publish krijgt een site-transient-lock (pd_rust_running, 30 min TTL) zodat parallelle cron-runs vanuit meerdere subsites niet meer hetzelfde bestand dubbel uploaden (zelfde bug als het Belle-incident v0.9.0 bij pd_run_daily).
 * 0.37.2 - Nijntje-stijl: geen vast zinnenantal (2026-06-17): verwijderd "PRECIES 5 zinnen per scène" — Nijntje-stijl gaat over eenvoud en directe taal, niet een vast aantal. Anker is totaal 150-200 woorden voor de juiste speelduur.
 * 0.37.1 - Nijntje-stijl bijgesteld (2026-06-16): 5 zinnen per scène (was 3) voor een speelduur van 1,5-2 minuten; max 8 woorden per zin, geen bijzinnen, totaal 150-200 woorden.
 * 0.37.0 - Nijntje-schrijfstijl (2026-06-16): verhaalprompten herschreven naar Dick Bruna-stijl: precies 5 zinnen per scène, maximaal 8 woorden per zin (liever 5-6), geen bijzinnen, totaal 150-200 woorden. Muziekvolume verlaagd naar 0.03 (via WP-optie pd_soundtrack_volume). Admin-label bijgewerkt.
 * 0.36.0 - EN bij hervatten (2026-06-16): pd_resume_stranded_unlocked genereert nu ook de Engelse vertaling, stem en Shotstack-render — identiek aan de normale dagelijkse run. Hierdoor mist de EN-aflevering niet meer als een run gestrande was.
 * 0.35.0 - Pre-gegenereerde afbeeldingen (2026-06-16): als Codex tijdens het schrijven al 5 scene-JPEG's + thumbnail in uploads/praatdeurtje-videos/pre/<sanitize_title(titel)>/ heeft gezet, kopieert de plugin ze naar de run-map en slaat alle OpenAI image-calls over (kostenbesparing ~$0,40/aflevering). Terugval op gewone generatie als de map ontbreekt of incompleet is.
 * 0.34.1 - Spotify-badge op de website (2026-06-15): "Luister op Spotify" knop met Spotify-logo onder elke verhalenblogpost (optie pd_spotify_url) + Spotify-link in de verhalen-categorie-banner naast de YouTube EN-link.
 * 0.34.0 - Engelse podcast-feed (2026-06-15): ?pd_podcast_en=1 geeft een Engelstalige RSS-feed (Kids & Family / Stories for Kids) voor Spotify for Creators / Apple Podcasts. EN mp3 (voice-en-*.mp3) blijft bewaard op de server na YouTube-upload. pd_podcast_en_register() wordt aangeroepen vanuit zowel pd_finalize (inline EN-blok) als pd_en_finalize (aparte taak). YouTube-beschrijving EN vermeldt de podcast.
 * 0.33.0 - Engelse variant (2026-06-15): zelfde 5 illustraties hergebruiken, Engelse GPT-4o-mini vertaling + ElevenLabs EN stem (pd_voice_id_en, standaard kLhAstPcnnPxqzk6gS5i) + aparte Shotstack-render -> YouTube afspeellijst "Mosje's Bedtime Stories" (pd_youtube_playlist_en, auto-aangemaakt). Engelse stappen mogen falen zonder de NL pipeline te raken. pd_en_pending voor afzonderlijke EN finalize als NL al klaar is maar EN render nog bezig. Beheer via Praatdeurtje > Engels (EN) in wp-admin.
 * 0.32.1 - Shotstack-submit idempotent (2026-06-11): per aflevering krijgen de lange video en Short elk een atomische submit-claim. Parallelle cron- en herstelprocessen kunnen daardoor niet meer dezelfde render dubbel insturen; een reeds ontvangen render-id wordt bij een retry hergebruikt.
 * 0.32.0 - begrijpelijke lessen (2026-06-11): lessen zijn concrete oorzaak-handeling-gevolg-situaties. Elk verhaal toont het lesje in drie zichtbare stappen en benoemt de kern eenmaal in een korte kindzin, zonder losse moraal.
 * 0.31.1 - herstel-lock en cooldown (2026-06-11): slechts één proces mag een gestrande aflevering hervatten. API- en billingfouten zetten een cooldown, zodat cron niet telkens dezelfde betaalde beeldgeneratie opnieuw start.
 * 0.31.0 - arc-deel = "een moment, geen dag" (2026-06-11): Mylene's feedback: in arc-mode voelde elke aflevering nog steeds als een hele mini-dag i.p.v. één moment uit een dag. Drie ingrepen, alleen in arc-mode (pd_arcs=1, default): (1) deel-prompts herschreven — ochtend/middag/avond beschrijven nu één rustig moment (geen doel, geen plan dat door 3 delen loopt, geen begin-midden-eind binnen één deel; verhaal mag in medias res beginnen en uitlopen). Deel 2 referreert niet meer terug ("eerder vandaag"-zin is weggehaald uit het in-verhaal-perspectief; chronologische volgorde leeft via de YT-playlist en het "Dit avontuur in delen"-blok). (2) OVERRIDE-blok in de user-prompt overschrijft het strikte verhaal-skelet uit v0.30: 'wens' = klein dingetje van nu, 'oplossing' = "het moment is voorbij", en de regel "hoofdwerkwoord in 3 van 5 scènes fysiek" wordt expliciet losgelaten. Bedtijd-afsluiting alleen nog in deel 3 (de avond). (3) Lager streefwoord-aantal in arc-mode (pd_arc_target_words, default 320 i.p.v. 480) — minder ruimte voor een mini-arc, meer ruimte voor sfeer. Zonder arc (pd_arcs=0) blijft het v0.30-skelet onveranderd. Cron blijft 1 video/dag om 17:00 NL.
 * 0.30.0 - verhaal-skelet afgedwongen (2026-06-10): gpt-4o produceerde sfeerstukken zonder plot (bv. "vader vogel fluistert iets, er wordt een stipje getekend, in de regen"). Drie ingrepen: (1) JSON-schema krijgt verplichte velden 'wens' (1 concrete zin: wat wil de hoofdpersoon vandaag), 'hoofdwerkwoord' (uit toegestane lijst: zoeken/maken/geven/brengen/repareren/planten/bouwen/verstoppen/vinden/delen/leren/helpen/oversteken/vangen) en 'oplossing' (1 concrete zin: hoe komt het er uit in scène 5); voelen/horen/kijken/fluisteren/dromen/wensen/denken zijn UITgesloten als hoofdwerkwoord. (2) Verhaal-skelet-blok in de prompt eist dat de wens door alle 5 scènes loopt en in scène 5 vervuld wordt, en dat het hoofdwerkwoord in min. 3/5 scènes fysiek gebeurt. (3) pd_seizoen_weer() voegt nu "het weer is DECOR, geen ONDERWERP" toe. Arc-deelinstructies (ochtend/middag/avond) verwijzen nu expliciet naar het concrete plan dat door alle 3 delen loopt — middag = volgende stap, avond = afronding — zodat dag-avonturen niet meer in elkaar overlopen als sfeer. Het kalme einde (iedereen tevreden naar bed) blijft staan: dat blokkeert spanning, niet plot.
 * 0.29.0 - schone dag-avontuur-titels (2026-06-10): " (deel X van 3: de Y)" wordt niet meer aan de post-titel geplakt. Alle 3 delen krijgen dezelfde basis-titel (Mosje zag dit als bug: leek rommelig in archief/feed). Het deel-nummer blijft volledig zichtbaar via het bestaande "Dit avontuur in delen"-blokje onder de post (post-meta pd_day_id/pd_part/pd_total). Bestaande titels opschonen via .ops/praatdeurtje-videos/cleanup-arc-titles.php (idempotent, draait WP-CLI op blog 5).
 * 0.28.1 - FB-backfill-knop (2026-06-09): admin-knop op Instellingen -> Praatdeurtje FB die alle al-gepubliceerde verhalen die nog geen FB-post hebben in een keer inplant (1 per 90s, oudste eerst). Veilig herhaalbaar via _pd_fb_posted-meta.
 * 0.28.0 - Facebook-publishing (2026-06-09): per aflevering automatisch een link-post (blog + YouTube-link in tekst) en een aparte foto-post (kleurplaat) naar de Praatdeurtje-FB-pagina (id 1068983399642608). Nieuwe events PD_FB_STORY (+120s na blogpost) en PD_FB_COLORING (+30s na kleurplaat-toevoeging). Tokens via Instellingen -> Praatdeurtje FB (Page Token is permanent). Dubbel-post-bescherming via post-meta _pd_fb_posted / _pd_fb_coloring_posted.
 * 0.27.1 - taalregel (2026-06-09): 'wiebelt/wiebelen/gewiebel' op de vermijden-lijst (kwam te vaak voor, o.a. 3x in ep 12 Belle). Uitzondering: Bloempje het konijntje wiebelt zijn neusje — dat is zijn vaste karakter-trekje en blijft toegestaan.
 * 0.27.0 - geen dubbele personages in dezelfde scène (2026-06-09): pd_image_character_lock injecteert per scene een harde regel dat ELK met-naam-genoemd personage EXACTLY ONCE in het beeld voorkomt — geen dubbelganger, geen tweeling, geen reflectie als zelfde personage. Aanleiding: in ep 17 (Kwakkel) verscheen Kwakkel twee keer in één frame. Bestaande "exactly once"-regel zat alleen onder de pose-sheet-conditional; nu altijd-aan voor alle benoemde cast.
 * 0.26.0 - rustvideo's 3x/week (2026-06-09): naast zondag + woensdag 18:00 NL ook vrijdag 18:00 NL één rustvideo uit de wachtrij (derde weekly event pd_rustvideo_event_late). Voorraad 27 → 9 weken; herfst-materiaal moet in sept/okt gefilmd om naadloos te kunnen continueren.
 * 0.25.0 - rustvideo's 2x/week (2026-06-09): naast zondag 18:00 NL ook woensdag 18:00 NL één rustvideo uit de wachtrij (tweede weekly event pd_rustvideo_event_mid → zelfde pd_rustvideo_publish). Bugfix: pd_rustvideo_publish sloot 'klaar-*' niet uit in de glob (sorteert vóór 'rustvideo-*'), waardoor een al-gepubliceerde video bij de volgende run opnieuw geüpload zou worden; nu gefilterd.
 * Changelog: 0.24.0 - dag-avonturen in delen (2026-06-08): één dag wordt over 3 afleveringen verteld (ochtend/middag/avond), elk bordurend op het vorige deel, zodat dagen/avonturen lang en rijk voelen zonder in één keer een lang verhaal te schrijven. Arc-state in optie pd_arc (day_id/part/lead/cast/place/lesson/title_base/so_far); pd_arc_plan() zet vóór het schrijven het deel klaar (nieuwe dag = cast+lesje kiezen via de v0.23-rotatie, nu op DAG-niveau; vervolgdelen hergebruiken dezelfde cast/hoofdrol/lesje), pd_arc_record() onthoudt na het schrijven de titel-basis en 'wat er tot nu toe gebeurde'. Elk deel staat op zichzelf (zachte terugblik-zin) en eindigt kalm; de avond sluit de dag slaperig af en laat het lesje landen. Volgorde-oplossing (feed = nieuwste eerst): deel-nummer in de titel ('(deel 1 van 3: de ochtend)', dash-vrij) + een aparte CHRONOLOGISCHE YouTube-afspeellijst 'Hele dagen in het Praatdeurtjesbos' (pd_arc_playlist, append=op volgorde) + een 'Dit avontuur in delen'-blokje onder elke blogpost dat live en op volgorde meegroeit (post-meta pd_day_id/pd_part/pd_total). Uit te zetten via optie pd_arcs=0 (terug naar v0.23 per-aflevering).
 * 0.23.0 - eerlijke rol-/plekrotatie + zacht lesje (2026-06-08): (1) de verhalenschrijver kiest niet langer zelf wie meedoet en waar (dat werd altijd Mosje + Kwakkel op dezelfde plekken). pd_cast_plan() bepaalt VOORAF, least-recently-used, welke vriendjes meedoen naast Mosje, wie de hoofdrol draagt en op welke plek het speelt; zo komt elk personage en elke plek echt aan de beurt. Mosje is er altijd bij en heeft echt interactie. Groepsgrootte varieert over een cyclus van 8 (meestal 1 vriendje, soms 2, af en toe een grote samen-dag met Mosje als gastheer). Bloempje (praat niet) komt nooit zonder Roosje; de nachtbloem doet alleen 's avonds mee en leidt nooit. Gebruik-geheugen in opties pd_spotlight_log/pd_place_log. (2) elk verhaaltje krijgt één klein, zacht levenslesje mee (lief zijn, helpen, luisteren, delen, geduld, eigen groente kweken, een plant/diertje leren kennen, ...), luchtig verweven en nooit als preek. Lesjes rouleren LRU (pd_lesson_log); eigen lesjes bijzetten via optie pd_lessen_extra.
 * 0.22.0 - feestdagenkalender (2026-06-07): op feestdagen krijgt de verhalenschrijver het thema automatisch mee (Pasen/Moederdag/Vaderdag berekend; verder zomer- en winterzonnewende, zacht Halloween, Sint-Maarten, Sinterklaas, Kerstavond, Kerst, Oudjaar). Wachtrij-items met datum gaan zoals altijd voor. Eigen thema's bijzetten of overschrijven via optie pd_feest_extra ('MM-DD' => instructie).
 * 0.21.0 - binnenkanten (2026-06-07): scènes kunnen zich nu BINNEN een woonplek afspelen zonder poppenhuis-doorsnede. Verhaal-schema: scenes[].indoor_place (naam van de plek). Beeldkant: binnen-ref ref-plekbinnen-<slug>-1.png gaat mee i.p.v. de buitenkant (en zonder binnen-ref wordt de buiten-ref bewust weggelaten), plus harde interieur-regels in de prompt (vanuit de kamer gezien, geen ontbrekende muur, raam mag het weer buiten tonen). Canon: optioneel veld binnen_en per plek (EN-beschrijving interieur, bewerkbaar op de Personages-pagina). Binnenkant-beelden komen van Codex/Mylene; bestaande bestanden winnen zoals altijd.
 * 0.20.0 - rustvideo's (2026-06-07): wekelijkse publicatie (zondag 18:00 NL) van echte slomo-opnames van de deurtjes+natuur, lokaal gemonteerd op de laptop. Wachtrij = mp4's in uploads/praatdeurtje-videos/rustvideos/ (optionele .txt: regel 1 titel, rest extra beschrijving). Upload naar het kanaal als "gemaakt voor kinderen" in een eigen afspeellijst (pd_rust_playlist, wordt bij de eerste keer automatisch aangemaakt: "Rustige momentjes uit het Praatdeurtjesbos"). Na publicatie hernoemd naar klaar-<datum>-... Handmatig: ?pd_rust=pd-rust-Mosje42. Cadansafspraak: 1/week (voorraad 315 clips = ~1 jaar); Shorts/Reels zijn hergebruik.
 * 0.19.0 - seizoenen-strip in de admin (2026-06-07): per plek vier vakjes (lente/zomer/herfst/winter) met ingangsdatum (1 mrt/1 jun/1 sep/1 dec) en het actieve seizoen gemarkeerd; per seizoen het beeld bekijken, vooruit genereren ("nu alvast maken", vanuit de basis-ref), verwijderen (met old-backup) of een eigen beeld uploaden (pd_szref_<seizoen>, gaat mee met Opslaan en wint altijd van de automaat). pd_seizoens_plekref accepteert nu een expliciet seizoen.
 * 0.18.1 - vervang je een plek-ref via de admin, dan gaan de seizoensvarianten van de oude ref mee naar de old-backup (anders bleven ze afgeleid van het oude beeld). Bevestigd gedrag: de pipeline overschrijft NOOIT bestaande refs/posebladen/seizoensbeelden - handmatige uploads winnen altijd.
 * 0.18.0 - seizoens-plekrefs (2026-06-07): plek-referenties rouleren automatisch mee met de meteorologische seizoenen (1 mrt/1 jun/1 sep/1 dec, zelfde kalender als het verhaal-weer). Basis-ref = zomer; lente/herfst/winter worden just-in-time eenmalig gegenereerd vanuit de basis-ref (zelfde deurdetails/compositie, alleen vegetatie/licht/weer anders) zodra een plek in dat seizoen voorkomt (ref-plekseizoen-<seizoen>-<slug>-1.png). Bij fout: stil terug naar de basis-ref. Personage-refs blijven seizoenloos (identity-only). Seizoensvarianten zichtbaar op de Personages-pagina per plek.
 * 0.17.1 - poseblad-upload in de admin (2026-06-07): op de Personages-pagina kan per personage een eigen poseblad geüpload worden (opgeslagen als poseblad-<slug>-1.png, oude versie krijgt een old-backup) + verwijderknop. Zo kan Mylene zelf posebladen maken en koppelen; de generator stuurt het blad van het hoofdpersonage automatisch mee (v0.14.1-gedrag, ongewijzigd).
 * 0.17.0 - tegoed-wachter: dagelijkse check voor de run mailt (max 1x per dag per dienst) zodra ElevenLabs < 10.000 tekens, OpenAI > 80% van het maandbudget (optie pd_openai_budget, standaard $60) of Shotstack naar schatting < 30 maandcredits. Mailadres via optie pd_alert_email (standaard myklijn@gmail.com). Plus: hoofdpersoon-regel - het personage dat de vraag draagt staat prominent op de thumbnail, Mosje alleen als hij echt meespeelt.
 * 0.16.1 - doorlopende dagdelen-cyclus: elke aflevering schuift een stap op (vroege ochtend -> ochtend -> middag -> namiddag -> schemering -> avond -> late avond -> nacht -> rond), zodat het bos doorleeft en elk verhaal het vorige opvolgt; seizoen en weer blijven echt; het einde blijft altijd kalm (bedtijd).
 * 0.16.0 - onverwoestbare runs + echt weer (2026-06-06): (1) voortgang wordt na ELKE stap bewaard (verhaal/beelden/thumbnail/stem) zodat een proces-kill nooit meer werk weggooit; (2) pd_resume_stranded maakt alleen het ontbrekende af; (3) zelfherstel: de cron hervat een gestrande run automatisch na 10 min; (4) pd_seizoen_weer(): seizoen + het echte Nederlandse weer van vandaag (Open-Meteo, gratis, 1x per dag gecachet) gaat in de verhaalprompt zodat Mosjes wereld meebeweegt met buiten.
 * 0.15.2 - twee kritieke fixes na ep 16: (1) QA-herkansing verwijderde per ongeluk de NIEUWE tekening (zelfde bestandsnaam) waardoor Shotstack "File not found" gaf; (2) een mislukte render gooit de assets niet meer weg maar bewaart ze als gestrande run (pd_resume).
 * 0.15.1 - gestrande-run-vangnet: mislukt het render-insturen (bv. Shotstack-tegoed op), dan worden verhaal+beelden+stem+thumbnail bewaard in pd_stranded en hervat ?pd_resume=pd-res-Mosje42 de run ZONDER nieuwe kosten (2026-06-06: twee complete runs verloren aan op-zijnde credits).
 * 0.15.0 - QA-check na generatie (2026-06-06): gpt-4o-mini (vision, ~0,2ct/beeld) controleert elke scene op harde fouten (personage dubbel of ontbreekt, leesbare tekst, meer dan 1 bal) en geeft max 1 herkansing per scene / 2 per aflevering met een gerichte FIX-instructie. Uit te zetten via optie pd_image_check=0. Visual signature bevat nu ook de pose van het hoofdpersonage.
 * 0.14.1 - posebladen: poseblad-<slug>-1.png van het hoofdpersonage gaat mee als referentie wanneer er ruimte is (max 3 refs), met een expliciete pas-op-regel (pose sheet = beweging begrijpen, personage exact 1x tekenen). Mosjes oogbeschrijving versterkt in de basislook.
 * 0.14.0 - posevrijheid (2026-06-06): referentiebeelden zijn alleen voor IDENTITEIT (uiterlijk/kleding/kleuren/proporties), niet voor houding. Canon-veld "poses" per personage (toegestane aanzichten/houdingen), per scene character_poses (view_angle/pose/gesture/expression) verplicht in het verhaal-JSON, in elke beeldprompt afgedwongen + de identity-only-regel. Mosje minstens 2 houdingen per aflevering en wisselende aanzichten.
 * 0.13.0 - beeldregie (2026-06-06): (1) thumbnail als aparte POSTER van de aflevering (8 compositietypes, rouleert; gebruikt als featured image en als YouTube custom thumbnail zodra het kanaal geverifieerd is); (2) visual_direction per scene (12 compositietypes, nooit 2x hetzelfde per aflevering, Mosje niet standaard links, minstens 1 object/plek-scene en 1 bijzondere camerahoek); (3) anti-herhalingsgeheugen: laatste 15 visuele handtekeningen + laatste thumbnail-types worden aan de verhaalplanner meegegeven.
 * 0.12.3 - continuity-vangnet: clausules over spulletjes van afwezige personages worden geschrapt (ep 14 kreeg een rode bal zonder Belle doordat gpt-4o het schema-voorbeeld napraatte); schema-voorbeeld geneutraliseerd.
 * 0.12.2 - kaart van het Praatdeurtjesbos op de Personages-pagina (wereldkaart.png + legenda); ligging vastgelegd in de wereldbeschrijving; woonplekken van alle personages als plekken met eigen referentiebeeld.
 * 0.12.1 - "Het web" bovenaan de Personages-pagina: per personage foto + woont-bij (met plek-koppeling) + eigen spulletjes + verboden in een oogopslag.
 * 0.12.0
 * Changelog: 0.12.0 - "scherper, slimmer" (2026-06-06): (1) props + verboden per personage/plek in de canon, deterministisch in elke beeldprompt geinjecteerd voor de aanwezige cast (eigendomsregel: Belle's bal alleen in beeld als Belle erbij is, vriendjes mogen er dan wel mee spelen); (2) de schrijver benoemt per scene expliciet characters/places (geen trefwoord-detectie meer als die velden er zijn - "a happy dog" triggerde personage Happy); (3) nieuwe personages/plekken krijgen in fase A EERST een referentiebeeld voordat de scenes getekend worden (bibliotheekje zag er per scene anders uit); (4) canon-merge dedupt op slug ("het kabouterbibliotheekje" vs "kabouterbibliotheekje"); (5) verbruiksteller per maand + echte OpenAI-maandkosten via optionele Admin-key (pd_openai_admin_key, invulveld in het tegoed-blok).
 * 0.11.0 - adminpagina "Personages & plekken" (2026-06-06): canon bekijken/bewerken in wp-admin met referentiebeelden in beeld + eigen afbeelding uploaden als nieuwe ref (oude ref krijgt automatisch een old-backup); ook wereldbeschrijving en plekken bewerkbaar. Plus tegoed-blok op de wachtrij-pagina: ElevenLabs-tekens (exact, met schatting resterende verhaaltjes), OpenAI-maandverbruik (indien admin-key, anders billing-link) en Shotstack-verwijzing; 10 min gecachet.
 * 0.10.0 - adminpagina "Verhalen wachtrij" (2026-06-06): wp-admin op blog 5 toont de script-wachtrij; per verhaal bewerken (titel, datum, continuity, 5 scenes), toevoegen, verwijderen, volgorde wijzigen + ruwe JSON-bewerking als vangnet. Statusblok met laatste run en openstaande render.
 * 0.9.3 - scène-continuïteit (2026-06-05): nieuw story-veld "continuity" (vaste prop-kleuren/-aantallen, seizoen, tijd van de dag) dat in elke beeldprompt wordt geïnjecteerd + "exactly ONCE, one single ball, never two"-regel. Aanleiding: in de Belle-aflevering wisselde de bal van kleur en stonden er twee ballen in beeld (de oude Belle-ref had zelf een bal in haar bek; refs van Happy en Belle zijn opnieuw gegenereerd uit de echte bronafbeeldingen).
 * 0.9.2 - embed + vindbaarheid (2026-06-05): (1) kses_remove_filters rond wp_insert_post/wp_update_post - cron heeft geen gebruiker (en multisite kent geen unfiltered_html) waardoor de YouTube-iframe uit elke blogpost werd gestript (lege <figure class="pd-video"> sinds 2026-06-03); (2) YouTube-beschrijving herschreven: sterke eerste regels (samenvatting), links naar verhalen-categorie en Wie-is-Mosje, rustige hashtags.
 * 0.9.1 - robuuste fase B (2026-06-05): (1) pd_download streamt naar schijf i.p.v. de mp4 in geheugen te bufferen - een verhaal van 143s gaf ~90MB en brak de 256MB-limiet (OOM-crash, aflevering verloren); (2) wp_raise_memory_limit als vangnet; (3) pd_pending wordt pas gewist NA de blogpost (met korte finalize-lock tegen dubbel posten) i.p.v. direct claimen, zodat een crash de aflevering niet meer wist; (4) wachtrij-item pas verwijderd na geslaagde fase A; (5) ignore_user_abort zodat de handmatige trigger niet sneuvelt bij disconnect.
 * 0.9.0 - een-per-dag-claim (pd_run_day): het daily event staat op elke subsite gepland; fase A duurt minuten waardoor parallelle starts de wachtrij leegtrokken (Belle-verhaal 2026-06-05). Claim direct bij start, fout-paden geven de claim vrij.
 * 0.8.0 — groei-pakket: (1) dagelijkse YouTube SHORT (9:16, scène 1) naast de volledige video; (2) podcast: voorlees-mp3 blijft bewaard + RSS-feed op ?pd_podcast=1 (gratis aanmelden bij Spotify); (3) winkel-CTA onderaan elke blogpost (optie pd_cta_url); (4) automatische KLEURPLAAT per aflevering (lijntekening van scène 1, event pd_coloring_event) onderaan de blogpost; (5) wachtrij-items kunnen een "date" (YYYY-MM-DD) hebben en spelen alleen op die datum (seizoensafleveringen vooruit plannen).
 * 0.7.0 — referentiebeelden per scène (alleen de karakters die er écht in zitten, ref-<naam>-*.png) + karakter-detectie en image-locks dynamisch uit de canon (nieuwe personages doen automatisch mee) + plek-refs (ref-plek-<naam>-*.png) + AUTOMATISCHE referentie-generatie: introduceert een aflevering een nieuw personage of nieuwe plek, dan wordt na publicatie een schoon referentiebeeld gemaakt uit de scène waarin het voorkomt (event pd_make_refs_event; handmatig: ?pd_refs=pd-refs-Mosje42 maakt ontbrekende refs bij).
 * 0.6.0 — geen gedachtestreepjes meer in gegenereerde tekst: taalregel in de gpt-prompt + vangnet (pd_no_dash/pd_clean_story) op titel, samenvatting en scènetekst. Klinkt menselijker.
 * 0.5.0 — beeld-prompt frisser (minder geel/sepia, geen geforceerde bloem/vuurvliegjes/avond; referentie alleen voor karakter, niet voor sfeer/tijd); lichte timeline-achtergrond; muziek uitschakelbaar (pd_soundtrack_url='none').
 * 0.4.0 — script-wachtrij (pd_script_queue, blog 5): vooraf geschreven afleveringen gaan vóór op auto-generatie (FIFO).
 * 0.3.0 — nieuwe video's vooraan in de afspeellijst (positie 0, nieuwste bovenaan in doorloop-speler) + race-fix in finalize (pending direct claimen) zodat een gelijktijdige run niet dubbel post.
 */

if (!defined('ABSPATH')) {
    exit;
}

const PD_CRON        = 'pd_daily_story';
const PD_FINALIZE    = 'pd_finalize_event';
const PD_EN_FINALIZE = 'pd_en_finalize_event';
const PD_MAKE_REFS   = 'pd_make_refs_event';
const PD_COLORING    = 'pd_coloring_event';
const PD_BLOG        = 5;        // praatdeurtje.nl
const PD_KEY_BLOG    = 7;        // gedeelde keys (ElevenLabs/Shotstack/YouTube-client) staan op blog 7
const PD_DAILY_TIME  = '17:00';  // NL-tijd; ruim voor bedtijd klaar
const PD_DIR         = WP_CONTENT_DIR . '/uploads/praatdeurtje-videos';
const PD_URL_BASE    = 'https://www.praatdeurtje.nl/wp-content/uploads/praatdeurtje-videos/';
const PD_PRE         = 0.6;      // korte rustige aanloop (fade-in)
const PD_POST        = 2.5;      // kalme uitloop na de stem
const PD_TARGET_WORDS = 480;     // streeflengte verhaal (tunebaar via optie pd_target_words)

/* ---- Opties: praatdeurtje op blog 5, gedeelde keys op blog 7 ---- */
function pd_get(string $k, $d = '') { return get_blog_option(PD_BLOG, $k, $d); }
function pd_set(string $k, $v): void { update_blog_option(PD_BLOG, $k, $v); }
function pd_key(string $k, $d = '') { return get_blog_option(PD_KEY_BLOG, $k, $d); }

function pd_log(string $msg): void {
    $log = (array) pd_get('pd_log', array());
    array_unshift($log, '[' . gmdate('Y-m-d H:i:s') . 'Z] ' . $msg);
    pd_set('pd_log', array_slice($log, 0, 120));
}

/* ---- Planning ---- */
function pd_next_run_ts(): int {
    try {
        $tz  = new DateTimeZone('Europe/Amsterdam');
        $now = new DateTime('now', $tz);
        $run = new DateTime($now->format('Y-m-d') . ' ' . PD_DAILY_TIME, $tz);
        if ($run <= $now) { $run->modify('+1 day'); }
        return $run->getTimestamp();
    } catch (\Throwable $e) { return time() + 3600; }
}

add_action('init', function () {
    if (!wp_next_scheduled(PD_CRON)) {
        wp_schedule_event(pd_next_run_ts(), 'daily', PD_CRON);
    }
    // v0.20: rustvideo's — zondag 18:00 NL één uit de wachtrij. v0.25 (2026-06-09):
    // tweede moment doordeweeks (woensdag 18:00) → cadans 2/week. v0.26 (2026-06-09):
    // derde moment (vrijdag 18:00) → cadans 3/week, zelfde publish-functie.
    add_action('pd_rustvideo_event', 'pd_rustvideo_publish');
    add_action('pd_rustvideo_event_mid', 'pd_rustvideo_publish');
    add_action('pd_rustvideo_event_late', 'pd_rustvideo_publish');
    if (!wp_next_scheduled('pd_rustvideo_event')) {
        try {
            $next = new DateTime('next sunday 18:00', new DateTimeZone('Europe/Amsterdam'));
            wp_schedule_event($next->getTimestamp(), 'weekly', 'pd_rustvideo_event');
        } catch (\Throwable $e) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'weekly', 'pd_rustvideo_event');
        }
    }
    if (!wp_next_scheduled('pd_rustvideo_event_mid')) {
        try {
            $nextMid = new DateTime('next wednesday 18:00', new DateTimeZone('Europe/Amsterdam'));
            wp_schedule_event($nextMid->getTimestamp(), 'weekly', 'pd_rustvideo_event_mid');
        } catch (\Throwable $e) {
            wp_schedule_event(time() + (4 * DAY_IN_SECONDS), 'weekly', 'pd_rustvideo_event_mid');
        }
    }
    if (!wp_next_scheduled('pd_rustvideo_event_late')) {
        try {
            $nextLate = new DateTime('next friday 18:00', new DateTimeZone('Europe/Amsterdam'));
            wp_schedule_event($nextLate->getTimestamp(), 'weekly', 'pd_rustvideo_event_late');
        } catch (\Throwable $e) {
            wp_schedule_event(time() + (2 * DAY_IN_SECONDS), 'weekly', 'pd_rustvideo_event_late');
        }
    }
    if (isset($_GET['pd_rust']) && hash_equals('pd-rust-Mosje42', (string) $_GET['pd_rust'])) { // handmatige trigger
        header('Content-Type: text/plain; charset=utf-8');
        pd_rustvideo_publish();
        echo "rustvideo-run klaar (zie logboek)\n";
        exit;
    }
    if (isset($_GET['pd_run']) && hash_equals('pd-test-Mosje42', (string) $_GET['pd_run'])) {
        header('Content-Type: text/plain; charset=utf-8');
        $r = pd_run_daily(true);
        echo is_wp_error($r) ? ('FOUT: ' . $r->get_error_message()) : ('OK fase A: ' . wp_json_encode($r, JSON_UNESCAPED_UNICODE));
        exit;
    }
    if (isset($_GET['pd_resume']) && hash_equals('pd-res-Mosje42', (string) $_GET['pd_resume'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo wp_json_encode(pd_resume_stranded(), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['pd_finalize']) && hash_equals('pd-fin-Mosje42', (string) $_GET['pd_finalize'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo wp_json_encode(pd_finalize(), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['pd_canon_show']) && hash_equals('pd-canon-Mosje42', (string) $_GET['pd_canon_show'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo pd_canon_text();
        exit;
    }
    if (isset($_GET['pd_refs']) && hash_equals('pd-refs-Mosje42', (string) $_GET['pd_refs'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo wp_json_encode(pd_make_refs(pd_missing_ref_jobs()), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (isset($_GET['pd_podcast'])) { // openbare podcast-feed (RSS), geen secret nodig
        pd_podcast_feed();
        exit;
    }
    if (isset($_GET['pd_podcast_en'])) { // Engelse podcast-feed (RSS)
        pd_podcast_en_feed();
        exit;
    }
});

add_action(PD_CRON, 'pd_tegoed_alerts', 5); // eerst waarschuwen, dan draaien
add_action(PD_CRON, 'pd_run_daily');

/* ---- Tegoed-wachter (v0.17): mailt VOORDAT een potje leeg is ----
 * Drempels: ElevenLabs < 10.000 tekens (±3 verhaaltjes), OpenAI > 80% van het
 * maandbudget (optie pd_openai_budget, standaard $60), Shotstack-schatting < 30
 * van de 200 maandcredits (±2 per aflevering). Max 1 mail per potje per dag. */
function pd_tegoed_alerts(): void {
    $today = gmdate('Y-m-d');
    $sent = (array) pd_get('pd_alert_sent', array());
    $alerts = array();

    $el = (string) pd_key('dhs_elevenlabs_api_key');
    if ('' !== $el) {
        $r = wp_remote_get('https://api.elevenlabs.io/v1/user/subscription', array('timeout' => 15, 'headers' => array('xi-api-key' => $el)));
        $d = is_wp_error($r) ? null : json_decode(wp_remote_retrieve_body($r), true);
        if (is_array($d) && isset($d['character_limit'])) {
            $left = max(0, (int) $d['character_limit'] - (int) ($d['character_count'] ?? 0));
            if ($left < 10000) { $alerts['elevenlabs'] = sprintf("ElevenLabs (stem) bijna op: nog %s tekens, dat is ongeveer %d verhaaltjes.\nBijvullen: elevenlabs.io -> Subscription.", number_format_i18n($left), (int) floor($left / 3000)); }
        }
    }

    $admin_key = (string) pd_get('pd_openai_admin_key');
    if ('' !== $admin_key) {
        $start = strtotime(gmdate('Y-m-01'));
        $r = wp_remote_get('https://api.openai.com/v1/organization/costs?start_time=' . $start . '&limit=31', array('timeout' => 15, 'headers' => array('Authorization' => 'Bearer ' . $admin_key)));
        if (!is_wp_error($r) && 200 === wp_remote_retrieve_response_code($r)) {
            $d = json_decode(wp_remote_retrieve_body($r), true);
            $sum = 0.0;
            foreach ((array) ($d['data'] ?? array()) as $b) { foreach ((array) ($b['results'] ?? array()) as $res) { $sum += (float) ($res['amount']['value'] ?? 0); } }
            $budget = (float) (pd_get('pd_openai_budget') ?: 60);
            if ($sum > 0.8 * $budget) { $alerts['openai'] = sprintf("OpenAI (verhaal + tekeningen) nadert het maandbudget: $%.2f van de ingestelde $%.0f.\nSaldo/limiet: platform.openai.com -> Billing. Budget aanpassen kan via optie pd_openai_budget.", $sum, $budget); }
        }
    }

    $c = (array) pd_get('pd_cost_month', array());
    $shot_used = (int) ($c['episodes'] ?? 0) * 2; // ±2 credits per aflevering incl. Short (schatting)
    $shot_left = 200 - $shot_used;
    if (($c['month'] ?? '') === gmdate('Y-m') && $shot_left < 30) {
        $alerts['shotstack'] = sprintf("Shotstack (video) raakt op volgens mijn schatting: ±%d van de 200 maandcredits over.\nEcht saldo: dashboard.shotstack.io (abonnement vernieuwt rond de 7e).", max(0, $shot_left));
    }

    $to = (string) (pd_get('pd_alert_email') ?: 'myklijn@gmail.com');
    foreach ($alerts as $type => $msg) {
        if (($sent[$type] ?? '') === $today) { continue; }
        wp_mail($to, '⚠️ Praatdeurtje tegoed-waarschuwing: ' . $type, $msg . "\n\nDeze melding komt maximaal 1x per dag per dienst.\n— de Praatdeurtje-pipeline");
        pd_log('Tegoed-waarschuwing gemaild (' . $type . ').');
        $sent[$type] = $today;
    }
    if ($alerts) { pd_set('pd_alert_sent', $sent); }
}

/** Hard minimum: is er genoeg om een COMPLETE aflevering te maken? Leeg = ja. */
function pd_tegoed_blokkade(): string {
    $el = (string) pd_key('dhs_elevenlabs_api_key');
    if ('' !== $el) {
        $r = wp_remote_get('https://api.elevenlabs.io/v1/user/subscription', array('timeout' => 15, 'headers' => array('xi-api-key' => $el)));
        $d = is_wp_error($r) ? null : json_decode(wp_remote_retrieve_body($r), true);
        if (is_array($d) && isset($d['character_limit'])) {
            $left = max(0, (int) $d['character_limit'] - (int) ($d['character_count'] ?? 0));
            if ($left < 3500) { return "ElevenLabs-tegoed te laag voor een verhaaltje ({$left} tekens over)."; }
        }
    }
    $c = (array) pd_get('pd_cost_month', array());
    if (($c['month'] ?? '') === gmdate('Y-m') && (200 - ((int) ($c['episodes'] ?? 0) * 2)) < 3) {
        return 'Shotstack-maandcredits naar schatting op.';
    }
    $admin_key = (string) pd_get('pd_openai_admin_key');
    if ('' !== $admin_key) {
        $start = strtotime(gmdate('Y-m-01'));
        $r = wp_remote_get('https://api.openai.com/v1/organization/costs?start_time=' . $start . '&limit=31', array('timeout' => 15, 'headers' => array('Authorization' => 'Bearer ' . $admin_key)));
        if (!is_wp_error($r) && 200 === wp_remote_retrieve_response_code($r)) {
            $d = json_decode(wp_remote_retrieve_body($r), true);
            $sum = 0.0;
            foreach ((array) ($d['data'] ?? array()) as $b) { foreach ((array) ($b['results'] ?? array()) as $res) { $sum += (float) ($res['amount']['value'] ?? 0); } }
            $budget = (float) (pd_get('pd_openai_budget') ?: 60);
            if ($sum >= $budget) { return sprintf('OpenAI-maandbudget bereikt ($%.2f van $%.0f).', $sum, $budget); }
        }
    }
    return '';
}
// v0.16: zelfherstel — een gestrande run wordt door de eerstvolgende cron-ping
// (elke 5 min) automatisch hervat zodra hij ouder is dan 10 minuten.
add_action('init', function () {
    if (!defined('DOING_CRON') || !DOING_CRON) { return; }
    $s = pd_get('pd_stranded');
    if (is_array($s) && !empty($s['story']) && (time() - (int) ($s['time'] ?? 0)) > 600 && !pd_get('pd_pending')) {
        pd_resume_stranded(false);
    }
}, 20);
add_action(PD_FINALIZE, 'pd_finalize');
add_action(PD_EN_FINALIZE, 'pd_en_finalize');
add_action(PD_MAKE_REFS, 'pd_make_refs', 10, 1);
add_action(PD_COLORING, 'pd_make_coloring', 10, 1);

/* ====================================================================
 * WERELD- EN KARAKTERBIJBEL (canon) — leeft in optie pd_canon (blog 5),
 * groeit mee: nieuwe personages/plekken worden na elke aflevering toegevoegd.
 * ==================================================================== */
function pd_canon_default(): array {
    return array(
        'world' => 'Het Praatdeurtjesbos: een zacht, mossig bos vol bomen met kleine pastelkleurige houten deurtjes, bloemetjes en vuurvliegjes. Altijd veilig en knus, vooral in de avond. De seizoenen mogen wisselen — het bos is extra mooi in de herfst.',
        'characters' => array(
            array('name' => 'Mosje', 'uiterlijk' => 'een lief klein, jong kaboutertje zónder baard, met een glad rond gezichtje, een rode puntmuts, mosgroen vestje, bruine laarsjes, ronde rode wangen en vriendelijke oogjes met duidelijke donkere pupillen', 'woont' => 'in een knus huisje in een mosrijke boom met een pastel houten deurtje', 'eigenschappen' => 'rustig, nieuwsgierig en zorgzaam; hoofdfiguur'),
            array('name' => 'Kwakkel de eend', 'uiterlijk' => 'een vriendelijke, zachte witte eend met een oranje snavel en oranje pootjes, rond en knuffelig', 'woont' => 'naast het meertje', 'eigenschappen' => 'nieuwsgierig en vrolijk, snatert graag'),
            array('name' => 'de zingende nachtbloem', 'uiterlijk' => 'een zacht gloeiende goudgele bloem (als een kleine waterlelie of tulp) die een warme gloed geeft', 'woont' => 'op de open plek', 'eigenschappen' => 'zingt een zacht liedje in de avond (noem het een "liedje", geen "melodie"); houden we licht, zonder veel achtergrond'),
        ),
        'places' => array(
            array('name' => "Mosje's boomhuisje", 'beschrijving' => 'een knus huisje in een mosrijke boom met een pastel deurtje'),
            array('name' => 'het meertje', 'beschrijving' => 'een klein, rustig meertje; Kwakkel de witte eend woont ernaast'),
            array('name' => 'de open plek', 'beschrijving' => "waar 's avonds de zingende nachtbloem zacht gloeit en een liedje zingt"),
            array('name' => 'de paddenstoelenkring', 'beschrijving' => 'een kring van paddenstoelen, een fijne plek om te zitten en te praten'),
            array('name' => 'de grote notenboom', 'beschrijving' => 'een oude, grote boom (noten/eikels), vooral mooi in de herfst'),
            array('name' => 'de appelboom', 'beschrijving' => 'een boom vol appels'),
            array('name' => 'de struiken', 'beschrijving' => 'lage struiken met bessen, fijn om je te verstoppen'),
            array('name' => 'de boerderij met moestuin', 'beschrijving' => 'een klein boerderijtje met een moestuin; misschien woont hier iemand (nog uit te bouwen)'),
        ),
    );
}
function pd_canon(): array {
    $c = pd_get('pd_canon', '');
    if (is_array($c)) { $canon = $c; } else { $canon = json_decode((string) $c, true); }
    if (!is_array($canon) || empty($canon['characters'])) { $canon = pd_canon_default(); }
    return $canon;
}
function pd_canon_save(array $canon): void { pd_set('pd_canon', wp_json_encode($canon, JSON_UNESCAPED_UNICODE)); }

function pd_canon_text(): string {
    $c = pd_canon();
    $out = "WERELD:\n" . ($c['world'] ?? '') . "\n\nPERSONAGES (blijf consistent met uiterlijk en waar ze wonen):\n";
    foreach (($c['characters'] ?? array()) as $ch) {
        $out .= '- ' . $ch['name'] . ': ' . ($ch['uiterlijk'] ?? '') . (empty($ch['woont']) ? '' : ('; woont ' . $ch['woont'])) . (empty($ch['eigenschappen']) ? '' : ('; ' . $ch['eigenschappen'])) . (empty($ch['poses']) ? '' : ('; toegestane poses/aanzichten: ' . $ch['poses'])) . ".\n";
    }
    $out .= "\nPLEKKEN IN HET BOS (gebruik bestaande plekken consistent):\n";
    foreach (($c['places'] ?? array()) as $pl) {
        // v0.21: plekken met een bekende binnenkant (beschrijving of binnen-ref) zijn
        // geschikt voor binnen-scènes — zo weet de schrijver waar hij naar binnen mag.
        $slug = pd_slugify((string) ($pl['name'] ?? ''));
        $heeft_binnen = !empty($pl['binnen_en']) || (glob(PD_DIR . '/ref-plekbinnen-' . $slug . '-*.png') ?: array());
        $out .= '- ' . $pl['name'] . ': ' . ($pl['beschrijving'] ?? '') . ($heeft_binnen ? ' [binnenkant bekend: hier mag een scène zich BINNEN afspelen via indoor_place]' : '') . ".\n";
    }
    return $out;
}
/* Naam -> bestandsnaam-veilige slug (voor ref-<slug>-*.png en detectie). */
function pd_slugify(string $name): string {
    $s = remove_accents(trim($name));
    $s = strtolower($s);
    $s = preg_replace('/^(de|het|een)\s+/', '', $s); // lidwoord eraf
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/* Vaste (hardcoded) locks voor de oorspronkelijke cast; canon-personages komen er dynamisch bij. */
function pd_base_locks(): array {
    return array(
        'mosje'      => 'Mosje is a sweet tiny YOUNG gnome child with NO beard and a smooth round little face, a tall red pointy felt hat, reddish-auburn hair peeking out under the hat, a moss-green tunic with a thin belt, brown boots, round rosy cheeks, a small round nose and LARGE warm friendly eyes with clear dark pupils and a soft gentle gaze like a curious young child (never tiny beady dot eyes, never a vacant stare; clean-shaven, childlike, never an old man, never a beard or moustache). ',
        'kwakkel'    => 'Kwakkel is a friendly soft white duck with an orange beak and orange feet, rounded and cuddly. ',
        'nachtbloem' => 'The singing nightflower is a soft glowing golden-yellow flower (like a little water-lily or tulip) giving off a warm gentle light. ',
    );
}

/* Engelse karakter-lock voor de illustraties (visuele consistentie, naast de referentiebeelden).
   Beschrijft alleen de karakters die in déze scène voorkomen — wie er niet in zit,
   wordt ook niet genoemd (anders nodigt de prompt uit om ze erbij te tekenen).
   Canon-personages (zoals Pip) doen automatisch mee: uiterlijk_en (EN) of anders uiterlijk (NL). */
function pd_image_character_lock(?array $present = null): string {
    $locks = pd_base_locks();
    foreach (pd_canon()['characters'] as $ch) {
        if (empty($ch['name'])) { continue; }
        $slug = pd_slugify((string) $ch['name']);
        if (isset($locks[$slug]) || pd_name_covers_base($ch['name'])) { continue; }
        $desc = (string) (!empty($ch['uiterlijk_en']) ? $ch['uiterlijk_en'] : ($ch['uiterlijk'] ?? ''));
        if ('' === $desc) { continue; }
        $locks[$slug] = $ch['name'] . ': ' . rtrim($desc, '. ') . '. ';
    }
    if (null === $present) { $present = array_keys($locks); } // backwards-compat
    $out = '';
    $names = array();
    foreach ($present as $slug) {
        if (isset($locks[$slug])) {
            $out .= $locks[$slug];
            // pak de naam terug uit de lock-string ("Naam: beschrijving...")
            if (preg_match('/^([^:]+):/', $locks[$slug], $m)) { $names[] = trim($m[1]); }
        }
    }
    if ('' === $out) { return ''; }
    // v0.27: harde uniciteits-regel — geen personage mag twee keer in hetzelfde frame staan.
    $names_txt = $names ? implode(', ', $names) : 'each named character';
    $unique = 'EACH named character ('
        . $names_txt
        . ') appears EXACTLY ONCE in this image — never duplicated, never twins, never a reflection or shadow shaped like the same character. If a character is in the scene, draw them exactly one time. ';
    return 'Keep the recurring characters EXACTLY consistent with the reference images and these notes: ' . $out . $unique;
}

/* Valt deze canon-naam al onder een hardcoded basiskarakter? (Mosje, Kwakkel de eend, de zingende nachtbloem) */
function pd_name_covers_base(string $name): bool {
    $n = mb_strtolower($name, 'UTF-8');
    foreach (array('mosje', 'kwakkel', 'nachtbloem') as $base) {
        if (false !== strpos($n, $base)) { return true; }
    }
    return false;
}
/* Voeg nieuwe personages/plekken die het verhaal introduceerde toe aan de canon. */
function pd_canon_merge(array $new_characters, array $new_places): array {
    $canon = pd_canon();
    $added = array('characters' => array(), 'places' => array());
    // Vergelijken op SLUG, niet op naam: "het kabouterbibliotheekje" en
    // "kabouterbibliotheekje" zijn dezelfde plek (2026-06-06: dubbele entry).
    $have = function ($list, $name) {
        $slug = pd_slugify((string) $name);
        foreach ($list as $i) { if (isset($i['name']) && pd_slugify((string) $i['name']) === $slug) { return true; } }
        return false;
    };
    foreach ($new_characters as $ch) {
        if (empty($ch['name']) || $have($canon['characters'], $ch['name'])) { continue; }
        $canon['characters'][] = array('name' => (string) $ch['name'], 'uiterlijk' => (string) ($ch['uiterlijk'] ?? ''), 'uiterlijk_en' => (string) ($ch['uiterlijk_en'] ?? ''), 'woont' => (string) ($ch['woont'] ?? ''), 'eigenschappen' => (string) ($ch['eigenschappen'] ?? ''));
        $added['characters'][] = (string) $ch['name'];
    }
    foreach ($new_places as $pl) {
        if (empty($pl['name']) || $have($canon['places'], $pl['name'])) { continue; }
        $canon['places'][] = array('name' => (string) $pl['name'], 'beschrijving' => (string) ($pl['beschrijving'] ?? ''), 'beschrijving_en' => (string) ($pl['beschrijving_en'] ?? ''));
        $added['places'][] = (string) $pl['name'];
    }
    if ($added['characters'] || $added['places']) {
        pd_canon_save($canon);
        pd_log('Bijbel uitgebreid — personages: [' . implode(', ', $added['characters']) . '] plekken: [' . implode(', ', $added['places']) . ']');
    }
    return $added;
}

/* ---- Verhaal-archief ---- */
function pd_story_log(): array {
    $raw = pd_get('dhs_praatdeurtje_story_log', '');
    if (is_array($raw)) { $log = $raw; } else { $log = json_decode((string) $raw, true); }
    if (!is_array($log)) { $log = array(); }
    usort($log, function ($a, $b) { return ((int) ($b['ep'] ?? 0)) <=> ((int) ($a['ep'] ?? 0)); });
    return $log;
}
function pd_append_story_log(int $ep, string $date, array $story): void {
    $log = pd_story_log();
    array_unshift($log, array(
        'ep' => $ep, 'date' => $date, 'title' => $story['title'],
        'summary' => $story['summary'] ?? '', 'characters' => $story['character'] ?? 'Mosje',
        'elements' => $story['elements'] ?? array(),
    ));
    pd_set('dhs_praatdeurtje_story_log', wp_json_encode(array_slice($log, 0, 60), JSON_UNESCAPED_UNICODE));
}

/* ====================================================================
 * FASE A — verhaal + tekeningen + stem + render INSTUREN (blijft < 5 min)
 * ==================================================================== */
function pd_run_daily(bool $manual = false) {
    // 900s + ignore_user_abort: fase A (5 beelden + terugval-retries) kan ruim
    // boven 280s uitkomen, en de handmatige ?pd_run-trigger mag niet sneuvelen
    // zodra de aanroepende client de verbinding sluit (2026-06-05: Belle-run
    // stierf 2x halverwege de beeldgeneratie).
    @set_time_limit(900);
    @ignore_user_abort(true);
    if (!is_dir(PD_DIR)) { wp_mkdir_p(PD_DIR); }
    $today = function () { try { return (new DateTime('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d'); } catch (\Throwable $e) { return gmdate('Y-m-d'); } };
    $log = pd_story_log();

    if (!$manual && !empty($log) && (($log[0]['date'] ?? '') === $today())) {
        pd_log('Overgeslagen: vandaag al een aflevering.'); return array('skipped' => 'vandaag al gedraaid');
    }
    if (pd_get('pd_pending')) {
        pd_log('Overgeslagen: er staat nog een render open (pd_pending).'); return array('skipped' => 'render nog bezig');
    }

    $openai = (string) pd_get('pd_openai_api_key');
    $el_key = (string) pd_key('dhs_elevenlabs_api_key');
    $shot   = (string) pd_key('dhs_shotstack_api_key');
    if ('' === $openai || '' === $el_key || '' === $shot) { pd_log('Afgebroken: key ontbreekt.'); return new WP_Error('pd_no_keys', 'Keys ontbreken.'); }

    // v0.17: PAUZEREN in plaats van halverwege stranden als een potje leeg is.
    $blok = pd_tegoed_blokkade();
    if ('' !== $blok) {
        if (!$manual) { pd_log('GEPAUZEERD: ' . $blok . ' Geen kosten gemaakt; de run hervat zodra het tegoed is aangevuld.'); return array('paused' => $blok); }
        pd_log('Waarschuwing (handmatige run gaat door): ' . $blok);
    }

    // Eén-per-dag-claim (zie 2026-06-05: drie parallelle fase A's omdat het
    // daily event op élke subsite gepland staat en fase A minuten duurt —
    // het Belle-verhaal viel daardoor ongepubliceerd uit de wachtrij).
    // Claim de dag DIRECT; fout-paden geven de claim weer vrij zodat een
    // herstart dezelfde dag mogelijk blijft.
    if (!$manual) {
        if (pd_get('pd_run_day') === $today()) {
            pd_log('Overgeslagen: vandaag is al een run gestart (dag-claim).');
            return array('skipped' => 'dag al geclaimd');
        }
        pd_set('pd_run_day', $today());
    }

    $ep = 1; foreach ($log as $e) { $ep = max($ep, (int) ($e['ep'] ?? 0) + 1); }
    $stamp = gmdate('Ymd-His');

    pd_log("Fase A start — aflevering {$ep}.");
    $story = pd_generate_story($openai, $log);
    if (is_wp_error($story)) { pd_log('Verhaal-fout: ' . $story->get_error_message()); if (!$manual) { pd_set('pd_run_day', ''); } return $story; }
    pd_log('Verhaal klaar: "' . $story['title'] . '".');

    // v0.16: voortgang DIRECT bewaren — sterft het proces (webserver-kill na ~8 min,
    // 2026-06-06: verhaal+beelden+stem verloren), dan maakt pd_resume_stranded alleen
    // het ontbrekende af. De cron herstelt gestrande runs automatisch na 10 min.
    $wip = array('stamp' => $stamp, 'ep' => $ep, 'story' => $story, 'images' => array(), 'thumb' => array(),
        'voice_url' => '', 'voice_local' => '', 'voice_duration' => 0.0, 'scene_end' => array(), 'date' => $today(), 'time' => time());
    pd_set('pd_stranded', $wip);

    // v0.12.3: continuity-vangnet — schrap clausules over spulletjes van personages
    // die niet meespelen (ep 14: gpt-4o papegaaide het schema-voorbeeld "red ball"
    // na, waardoor Belles bal in een verhaal zonder Belle opdook).
    if (!empty($story['continuity'])) {
        $cast_all = array();
        foreach ((array) $story['scenes'] as $sc) { list($cs) = pd_scene_cast((array) $sc); $cast_all = array_merge($cast_all, $cs); }
        $cast_all = array_unique($cast_all);
        $clauses = array_filter(array_map('trim', explode(';', (string) $story['continuity'])));
        $keep = array();
        foreach ($clauses as $cl) {
            $low = mb_strtolower($cl, 'UTF-8');
            if (false !== strpos($low, 'ball') && !in_array('belle', $cast_all, true)) { pd_log('Continuity-clausule geschrapt (bal zonder Belle): "' . $cl . '".'); continue; }
            $drop = false;
            foreach (pd_canon()['characters'] as $ch) {
                $slug = pd_canon_slug((string) ($ch['name'] ?? ''));
                if ('' === $slug || in_array($slug, $cast_all, true)) { continue; }
                if (false !== strpos($low, mb_strtolower((string) $ch['name'], 'UTF-8'))) { $drop = true; pd_log('Continuity-clausule geschrapt (' . $ch['name'] . ' speelt niet mee): "' . $cl . '".'); break; }
            }
            if (!$drop) { $keep[] = $cl; }
        }
        $story['continuity'] = implode('; ', $keep);
    }

    // v0.12: nieuwe personages/plekken NU al in de canon zetten en er ÉÉRST een
    // referentiebeeld voor maken, zodat alle 5 scènes dezelfde versie tekenen
    // (het kabouterbibliotheekje zag er per scène anders uit: ref kwam pas ná publicatie).
    pd_canon_merge((array) ($story['new_characters'] ?? array()), (array) ($story['new_places'] ?? array()));
    $pre_jobs = pd_fase_a_ref_jobs($story);
    if ($pre_jobs) {
        pd_log('Eerst referentiebeelden maken voor: ' . implode(', ', wp_list_pluck($pre_jobs, 'name')) . '.');
        for ($i = 0; $i < count($pre_jobs); $i += 2) { pd_make_refs(array_slice($pre_jobs, $i, 2)); }
    }

    // v0.35: pre-gegenereerde afbeeldingen van Codex hergebruiken (sla OpenAI image-calls over)
    $pre_slug = sanitize_title((string) ($story['title'] ?? ''));
    $pre_dir  = PD_DIR . '/pre/' . $pre_slug;
    $pre_used = false;
    $images   = array();
    if ('' !== $pre_slug && is_dir($pre_dir)) {
        $pre_imgs = array();
        for ($pi = 1; $pi <= 7; $pi++) {
            $p   = $pre_dir . '/scene-' . $pi . '.jpg';
            $dst = PD_DIR . '/scene-' . $stamp . '-' . $pi . '.jpg';
            if (!file_exists($p) || !@copy($p, $dst)) { $pre_imgs = array(); break; }
            $pre_imgs[] = array('local' => $dst, 'url' => PD_URL_BASE . basename($dst));
        }
        if (count($pre_imgs) === 5) {
            $images = $pre_imgs; $pre_used = true;
            pd_log('Pre-gegenereerde tekeningen van Codex gebruikt (' . $pre_slug . ').');
        }
    }

    $qa_retries = 0;
    if (!$pre_used) {
        // max 2 herkansingen per aflevering (kostengrens)
        foreach ($story['scenes'] as $i => $sc) {
            $vd = is_array($sc['visual_direction'] ?? null) ? $sc['visual_direction'] : null;
            $cp = is_array($sc['character_poses'] ?? null) ? $sc['character_poses'] : null;
            $cast = pd_scene_cast((array) $sc);
            $img = pd_generate_image($openai, (string) $sc['image'], $stamp, $i + 1, (string) ($sc['text'] ?? ''), (string) ($story['continuity'] ?? ''), $cast, $vd, $cp, (string) ($sc['indoor_place'] ?? ''));
            if (is_wp_error($img)) { pd_log('Beeld-fout scene ' . ($i + 1) . ': ' . $img->get_error_message()); if (!$manual) { pd_set('pd_run_day', ''); } return $img; }
            // v0.15: QA-check — harde fouten (dubbel personage, leesbare tekst, twee ballen) -> 1 herkansing
            $check = pd_image_check($openai, (string) $img['local'], $cast[0]);
            if (empty($check['ok']) && $qa_retries < 2) {
                $qa_retries++;
                pd_log('QA scene ' . ($i + 1) . ' afgekeurd (' . implode('; ', (array) $check['problems']) . ') — herkansing.');
                $fix = '' !== (string) ($check['retry_direction'] ?? '') ? (' FIX THIS: ' . $check['retry_direction']) : '';
                $img2 = pd_generate_image($openai, (string) $sc['image'] . $fix, $stamp, $i + 1, (string) ($sc['text'] ?? ''), (string) ($story['continuity'] ?? ''), $cast, $vd, $cp, (string) ($sc['indoor_place'] ?? ''));
                // GEEN unlink: de herkansing schrijft naar hetzelfde bestand (zelfde stamp+nummer);
                // unlinken verwijderde de nieuwe tekening (2026-06-06: Shotstack "File not found").
                if (!is_wp_error($img2)) { $img = $img2; }
            }
            $images[] = $img;
            // visuele handtekening (compositie + pose hoofdpersonage) voor het anti-herhalingsgeheugen
            $pose_sig = '';
            if (is_array($cp) && !empty($cp[0]['name'])) { $pose_sig = '; ' . $cp[0]['name'] . ' ' . trim((string) ($cp[0]['view_angle'] ?? '') . ' ' . (string) ($cp[0]['pose'] ?? '')); }
            if ($vd) { pd_visual_log_add((string) ($vd['composition_type'] ?? '?') . ': ' . (string) ($vd['character_placement'] ?? ($vd['main_visual_focus'] ?? '')) . $pose_sig); }
        }
        pd_log('5 tekeningen klaar (JPEG)' . ($qa_retries ? " — {$qa_retries} QA-herkansing(en)" : '') . '.');
    }
    $wip['images'] = $images; $wip['time'] = time(); pd_set('pd_stranded', $wip);

    // v0.13: thumbnail als aparte poster (mag falen zonder de run te raken)
    // v0.35: pre-gegenereerde thumbnail hergebruiken indien beschikbaar
    $thumb = array();
    if ($pre_used && file_exists($pre_dir . '/thumbnail.jpg')) {
        $dst_thumb = PD_DIR . '/thumb-' . $stamp . '.jpg';
        if (@copy($pre_dir . '/thumbnail.jpg', $dst_thumb)) {
            $thumb = array('local' => $dst_thumb, 'url' => PD_URL_BASE . basename($dst_thumb));
            pd_log('Pre-gegenereerde thumbnail van Codex gebruikt.');
        }
    }
    if (empty($thumb['local']) && is_array($story['thumbnail'] ?? null) && !empty($story['thumbnail']['main_focus'])) {
        $thumb = pd_generate_thumbnail($openai, (array) $story['thumbnail'], $stamp);
        if (is_wp_error($thumb)) { pd_log('Thumbnail-fout (gaat door met scène 1): ' . $thumb->get_error_message()); $thumb = array(); }
        else {
            pd_log('Thumbnail klaar (' . (string) ($story['thumbnail']['composition_type'] ?? '?') . ').');
            $tt = (array) pd_get('pd_thumb_types', array());
            array_unshift($tt, (string) ($story['thumbnail']['composition_type'] ?? '?'));
            pd_set('pd_thumb_types', array_slice($tt, 0, 5));
        }
    }

    $wip['thumb'] = $thumb; $wip['time'] = time(); pd_set('pd_stranded', $wip);

    $voice = pd_elevenlabs($el_key, $story['scenes'], $stamp);
    if (is_wp_error($voice)) { pd_log('Stem-fout: ' . $voice->get_error_message()); if (!$manual) { pd_set('pd_run_day', ''); } return $voice; }
    pd_log('Stem klaar (' . round($voice['duration'], 1) . 's).');
    $wip['voice_url'] = $voice['url']; $wip['voice_local'] = $voice['local']; $wip['voice_duration'] = (float) $voice['duration']; $wip['scene_end'] = (array) $voice['scene_end']; $wip['time'] = time();
    pd_set('pd_stranded', $wip);

    $env = pd_key('dhs_shotstack_env') ?: 'v1';
    $render_id = pd_shotstack_submit($shot, $env, $story['title'], $images, $voice, $stamp);
    if (is_wp_error($render_id)) {
        // v0.15.1: assets bewaren bij render-fout (Shotstack-credits op, 2026-06-06:
        // twee complete runs verloren). Hervatten zonder nieuwe kosten:
        // ?pd_resume=pd-res-Mosje42 zodra het tegoed is aangevuld.
        pd_set('pd_stranded', array(
            'stamp' => $stamp, 'ep' => $ep, 'story' => $story, 'images' => $images, 'thumb' => $thumb,
            'voice_url' => $voice['url'], 'voice_local' => $voice['local'], 'voice_duration' => (float) $voice['duration'], 'scene_end' => $voice['scene_end'],
            'date' => $today(), 'time' => time(),
        ));
        pd_log('Render-insturen mislukt — ALLE assets bewaard (pd_stranded). Hervat met ?pd_resume=pd-res-Mosje42 na aanvullen Shotstack-tegoed. Fout: ' . $render_id->get_error_message());
        if (!$manual) { pd_set('pd_run_day', ''); }
        return $render_id;
    }

    // Daarnaast een verticale Short (scène 1) — mag falen zonder de hoofdrun te raken.
    $short_id = pd_shotstack_submit_short($shot, $env, $story['title'], $images, $voice, $stamp);
    if (is_wp_error($short_id)) { pd_log('Short-insturen mislukt (gaat door zonder): ' . $short_id->get_error_message()); $short_id = ''; }

    // v0.33: Engelse variant — zelfde beelden, Engelse vertaling + stem + render (mag falen zonder NL te raken).
    $en_render_id = ''; $en_story_out = array();
    if (pd_en_enabled()) {
        $en_t = pd_translate_en($openai, $story);
        if (!is_wp_error($en_t)) {
            $en_v = pd_elevenlabs_en($el_key, $en_t['scenes'], $stamp);
            if (!is_wp_error($en_v)) {
                $en_r = pd_shotstack_submit_en($shot, $env, $en_t['title'], $images, $en_v, $stamp);
                if (!is_wp_error($en_r)) { $en_render_id = (string) $en_r; $en_story_out = $en_t; pd_log('Engelse render ingestuurd: ' . $en_render_id . '.'); }
                else { pd_log('Engelse render mislukt (NL gaat door): ' . $en_r->get_error_message()); }
            } else { pd_log('Engelse stem mislukt (NL gaat door): ' . $en_v->get_error_message()); }
        } else { pd_log('Engelse vertaling mislukt (NL gaat door): ' . $en_t->get_error_message()); }
    }

    // Fase A geslaagd — NU pas het wachtrij-item definitief verwijderen.
    if (!empty($story['_queue_title'])) {
        pd_queue_remove((string) $story['_queue_title']);
        unset($story['_queue_title']);
    }

    pd_set('pd_pending', array(
        'render_id' => $render_id, 'short_render_id' => $short_id, 'en_render_id' => $en_render_id, 'en_story' => $en_story_out,
        'env' => $env, 'ep' => $ep, 'stamp' => $stamp,
        'story' => $story, 'images' => $images, 'thumb' => $thumb,
        'voice_local' => $voice['local'], 'voice_duration' => (float) $voice['duration'], 'date' => $today(), 'attempts' => 0,
    ));
    pd_set('pd_stranded', ''); // run veilig ingestuurd: werkkopie niet meer nodig
    wp_schedule_single_event(time() + 90, PD_FINALIZE);
    pd_log("Fase A klaar — render {$render_id} ingestuurd, fase B ingepland (~90s).");
    return array('phase' => 'A', 'ep' => $ep, 'title' => $story['title'], 'render_id' => $render_id, 'duration' => $voice['duration']);
}

/* v0.15.1: gestrande run hervatten — alleen de render opnieuw insturen,
 * beelden/stem/thumbnail worden hergebruikt (geen nieuwe kosten). */
function pd_resume_lock_acquire() {
    $key = 'pd_resume_lock';
    $now = time();
    $old = pd_get($key);
    if (is_array($old) && ($now - (int) ($old['time'] ?? 0)) > 1200) {
        delete_option($key);
    }
    $token = wp_generate_uuid4();
    return add_option($key, array('token' => $token, 'time' => $now), '', false) ? $token : '';
}

function pd_resume_lock_release(string $token): void {
    $lock = pd_get('pd_resume_lock');
    if (is_array($lock) && hash_equals((string) ($lock['token'] ?? ''), $token)) {
        delete_option('pd_resume_lock');
    }
}

function pd_resume_cooldown(string $message): void {
    $lower = strtolower($message);
    $seconds = (false !== strpos($lower, 'billing') || false !== strpos($lower, 'hard limit')) ? 3600 : 900;
    pd_set('pd_resume_cooldown_until', time() + $seconds);
}

function pd_resume_stranded(bool $manual = true) {
    $cooldown = (int) pd_get('pd_resume_cooldown_until', 0);
    if (!$manual && $cooldown > time()) {
        return array('cooldown' => $cooldown - time());
    }
    $token = pd_resume_lock_acquire();
    if ('' === $token) {
        return array('busy' => 'herstel is al bezig');
    }
    try {
        if (!$manual) {
            $stranded = pd_get('pd_stranded');
            if (!is_array($stranded) || empty($stranded['story'])) {
                return array('idle' => 'geen gestrande run');
            }
            pd_log('Zelfherstel: gestrande run "' . (string) ($stranded['story']['title'] ?? '?') . '" wordt automatisch hervat.');
        }
        $result = pd_resume_stranded_unlocked();
        if (!empty($result['resumed'])) { pd_set('pd_resume_cooldown_until', 0); }
        return $result;
    } finally {
        pd_resume_lock_release($token);
    }
}

function pd_resume_stranded_unlocked() {
    @set_time_limit(900);
    @ignore_user_abort(true);
    $s = pd_get('pd_stranded');
    if (!is_array($s) || empty($s['story'])) { return array('idle' => 'geen gestrande run'); }
    if (pd_get('pd_pending')) { return array('busy' => 'er staat al een render open'); }
    $shot = (string) pd_key('dhs_shotstack_api_key');
    $env  = pd_key('dhs_shotstack_env') ?: 'v1';
    $story = (array) $s['story'];
    $images = (array) $s['images'];
    $stamp = (string) $s['stamp'];

    // v0.16: ontbrekende stappen afmaken (het proces kan halverwege gestorven zijn)
    $openai = (string) pd_get('pd_openai_api_key');
    $made = array();
    foreach ((array) $story['scenes'] as $i => $sc) {
        if (!empty($images[$i]['local']) && file_exists((string) $images[$i]['local'])) { continue; }
        $vd = is_array($sc['visual_direction'] ?? null) ? $sc['visual_direction'] : null;
        $cp = is_array($sc['character_poses'] ?? null) ? $sc['character_poses'] : null;
        $img = pd_generate_image($openai, (string) $sc['image'], $stamp, $i + 1, (string) ($sc['text'] ?? ''), (string) ($story['continuity'] ?? ''), pd_scene_cast((array) $sc), $vd, $cp, (string) ($sc['indoor_place'] ?? ''));
        if (is_wp_error($img)) {
            $message = $img->get_error_message();
            pd_resume_cooldown($message);
            pd_log('Hervatten: beeld ' . ($i + 1) . ' mislukt: ' . $message);
            return array('error' => $message);
        }
        $images[$i] = $img; $made[] = 'scène ' . ($i + 1);
        $s['images'] = $images; $s['time'] = time(); pd_set('pd_stranded', $s); // tussenstand bewaren
    }
    ksort($images); $images = array_values($images);
    $thumb = (array) ($s['thumb'] ?? array());
    if (empty($thumb['local']) && is_array($story['thumbnail'] ?? null) && !empty($story['thumbnail']['main_focus'])) {
        $t = pd_generate_thumbnail($openai, (array) $story['thumbnail'], $stamp);
        if (!is_wp_error($t)) { $thumb = $t; $made[] = 'thumbnail'; $s['thumb'] = $thumb; pd_set('pd_stranded', $s); }
    }
    if ('' === (string) $s['voice_local'] || !file_exists((string) $s['voice_local'])) {
        $el_key = (string) pd_key('dhs_elevenlabs_api_key');
        $v = pd_elevenlabs($el_key, (array) $story['scenes'], $stamp);
        if (is_wp_error($v)) {
            $message = $v->get_error_message();
            pd_resume_cooldown($message);
            pd_log('Hervatten: stem mislukt: ' . $message);
            return array('error' => $message);
        }
        $s['voice_url'] = $v['url']; $s['voice_local'] = $v['local']; $s['voice_duration'] = (float) $v['duration']; $s['scene_end'] = (array) $v['scene_end'];
        $made[] = 'stem';
        pd_set('pd_stranded', $s);
    }
    if ($made) { pd_log('Hervatten: bijgemaakt — ' . implode(', ', $made) . '.'); }
    $voice = array('url' => (string) $s['voice_url'], 'local' => (string) $s['voice_local'], 'duration' => (float) $s['voice_duration'], 'scene_end' => (array) $s['scene_end']);
    $render_id = pd_shotstack_submit($shot, $env, (string) $story['title'], $images, $voice, $stamp);
    if (is_wp_error($render_id)) {
        $message = $render_id->get_error_message();
        pd_resume_cooldown($message);
        pd_log('Hervatten mislukt (render): ' . $message);
        return array('error' => $message);
    }
    $short_id = pd_shotstack_submit_short($shot, $env, (string) $story['title'], $images, $voice, $stamp);
    if (is_wp_error($short_id)) { $short_id = ''; }

    // v0.36: EN ook bij hervatten (was eerder overgeslagen — zelfde blok als pd_run_daily).
    $el_key = (string) pd_key('dhs_elevenlabs_api_key');
    $en_render_id = ''; $en_story_out = array();
    if (pd_en_enabled() && '' !== $el_key && '' !== $openai) {
        $en_t = pd_translate_en($openai, $story);
        if (!is_wp_error($en_t)) {
            $en_v = pd_elevenlabs_en($el_key, $en_t['scenes'], $stamp);
            if (!is_wp_error($en_v)) {
                $en_r = pd_shotstack_submit_en($shot, $env, $en_t['title'], $images, $en_v, $stamp);
                if (!is_wp_error($en_r)) { $en_render_id = (string) $en_r; $en_story_out = $en_t; pd_log('Engelse render ingestuurd (hervatten): ' . $en_render_id . '.'); }
                else { pd_log('Engelse render mislukt (hervatten, gaat door): ' . $en_r->get_error_message()); }
            } else { pd_log('Engelse stem mislukt (hervatten, gaat door): ' . $en_v->get_error_message()); }
        } else { pd_log('Engelse vertaling mislukt (hervatten, gaat door): ' . $en_t->get_error_message()); }
    }

    if (!empty($story['_queue_title'])) { pd_queue_remove((string) $story['_queue_title']); unset($story['_queue_title']); }
    pd_set('pd_pending', array(
        'render_id' => $render_id, 'short_render_id' => $short_id, 'en_render_id' => $en_render_id, 'en_story' => $en_story_out,
        'env' => $env, 'ep' => (int) $s['ep'], 'stamp' => (string) $s['stamp'],
        'story' => $story, 'images' => $images, 'thumb' => (array) ($s['thumb'] ?? array()),
        'voice_local' => $voice['local'], 'voice_duration' => $voice['duration'], 'date' => (string) $s['date'], 'attempts' => 0,
    ));
    pd_set('pd_stranded', '');
    wp_schedule_single_event(time() + 90, PD_FINALIZE);
    pd_log('Gestrande run hervat — render ' . $render_id . ' ingestuurd, fase B ingepland.');
    return array('resumed' => true, 'ep' => (int) $s['ep'], 'title' => (string) $story['title'], 'render_id' => $render_id);
}

/* ====================================================================
 * FASE B — render OPHALEN -> YouTube -> blog -> bijbel -> opruimen
 * ==================================================================== */
function pd_finalize() {
    @set_time_limit(280);
    if (function_exists('wp_raise_memory_limit')) { wp_raise_memory_limit('image'); }
    $p = pd_get('pd_pending');
    if (!is_array($p) || empty($p['render_id'])) { return array('idle' => 'geen openstaande render'); }

    $shot = (string) pd_key('dhs_shotstack_api_key');
    $poll = pd_shotstack_poll($shot, (string) $p['env'], (string) $p['render_id']);
    if ('failed' === $poll['status']) {
        // v0.15.2: NIET meer de assets weggooien — bewaren als gestrande run zodat
        // een hervatting (?pd_resume) de render gratis opnieuw kan insturen.
        pd_set('pd_stranded', array(
            'stamp' => (string) $p['stamp'], 'ep' => (int) $p['ep'], 'story' => (array) $p['story'], 'images' => (array) $p['images'], 'thumb' => (array) ($p['thumb'] ?? array()),
            'voice_url' => PD_URL_BASE . basename((string) $p['voice_local']), 'voice_local' => (string) $p['voice_local'], 'voice_duration' => (float) $p['voice_duration'],
            'scene_end' => array(), 'date' => (string) $p['date'], 'time' => time(),
        ));
        pd_set('pd_pending', '');
        pd_log('Render mislukt — assets bewaard (pd_stranded), hervat met ?pd_resume=pd-res-Mosje42.');
        return array('failed' => true, 'stranded' => true);
    }
    if ('done' !== $poll['status']) {
        $p['attempts'] = (int) $p['attempts'] + 1;
        if ($p['attempts'] > 10) { pd_log('Render-timeout (te veel pogingen) — pending gewist.'); pd_set('pd_pending', ''); return array('timeout' => true); }
        pd_set('pd_pending', $p);
        wp_schedule_single_event(time() + 60, PD_FINALIZE);
        return array('status' => 'nog aan het renderen', 'attempt' => $p['attempts']);
    }

    // Dubbel-post-bescherming MÉT behoud van data: een korte lock (geen wipe).
    // pd_pending blijft staan tot de blogpost gemaakt is — zo overleeft de
    // aflevering een crash (2026-06-05: OOM in download wiste de hele run).
    $lock = (int) pd_get('pd_finalize_lock', 0);
    if ($lock && (time() - $lock) < 280) { return array('skipped' => 'finalize al bezig'); }
    pd_set('pd_finalize_lock', time());

    // klaar -> downloaden
    $story = (array) $p['story']; $images = (array) $p['images']; $ep = (int) $p['ep'];
    $fname = 'verhaal-' . $p['stamp'] . '.mp4';
    $local = PD_DIR . '/' . $fname;
    $final_url = pd_download($poll['url'], $local) ? (PD_URL_BASE . $fname) : $poll['url'];

    $thumb = is_array($p['thumb'] ?? null) ? $p['thumb'] : array();
    $yt = pd_post_youtube($local, $story, $ep, false, (string) ($thumb['local'] ?? ''));
    $yt_id = (is_string($yt) && strpos($yt, 'youtu.be/') === 0) ? substr($yt, strlen('youtu.be/')) : '';

    // Short ophalen + uploaden (mag falen zonder de rest te raken)
    $short = '';
    if (!empty($p['short_render_id'])) {
        $sp = pd_shotstack_poll($shot, (string) $p['env'], (string) $p['short_render_id']);
        if ('done' === $sp['status'] && '' !== $sp['url']) {
            $slocal = PD_DIR . '/short-' . $p['stamp'] . '.mp4';
            if (pd_download($sp['url'], $slocal)) {
                $short = pd_post_youtube($slocal, $story, $ep, true);
                if (is_wp_error($short)) { pd_log('Short-upload mislukt: ' . $short->get_error_message()); $short = ''; }
                else { pd_log('Short live: ' . (is_string($short) ? $short : '')); }
                @unlink($slocal);
            }
        } else {
            pd_log('Short niet klaar (' . $sp['status'] . ') — overgeslagen voor vandaag.');
        }
    }

    $post_id = pd_create_blog_post($story, $images, $final_url, $yt_id, $ep, (string) ($thumb['url'] ?? ''));
    $post_id_int = is_wp_error($post_id) ? 0 : (int) $post_id;

    // v0.33: Engelse YouTube-upload — na blogpost zodat we post_id in meta kunnen opslaan.
    if (!empty($p['en_render_id']) && !empty($p['en_story'])) {
        $enp = pd_shotstack_poll($shot, (string) $p['env'], (string) $p['en_render_id']);
        if ('done' === $enp['status'] && '' !== $enp['url']) {
            $en_local = PD_DIR . '/en-verhaal-' . $p['stamp'] . '.mp4';
            if (pd_download($enp['url'], $en_local)) {
                $en_yt = pd_post_youtube_en($en_local, (array) $p['en_story'], $ep);
                if (is_wp_error($en_yt)) { pd_log('Engelse YT mislukt: ' . $en_yt->get_error_message()); }
                else {
                    pd_log('Engelse YT live: ' . $en_yt);
                    if ($post_id_int) { switch_to_blog(PD_BLOG); update_post_meta($post_id_int, '_pd_youtube_en_url', (string) $en_yt); restore_current_blog(); }
                    pd_podcast_en_register($ep, (array) $p['en_story'], (string) $p['stamp'], $post_id_int);
                }
                @unlink($en_local); // mp4 weg; voice-en-*.mp3 blijft voor de podcast-feed
            }
        } elseif ('failed' === $enp['status']) {
            pd_log('Engelse render mislukt — overgeslagen.');
        } else {
            pd_set('pd_en_pending', array('render_id' => (string) $p['en_render_id'], 'env' => (string) $p['env'], 'story' => (array) $p['en_story'], 'ep' => $ep, 'stamp' => (string) $p['stamp'], 'post_id' => $post_id_int, 'attempts' => 0));
            wp_schedule_single_event(time() + 90, PD_EN_FINALIZE);
            pd_log('Engelse render nog bezig — aparte taak ingepland.');
        }
    }

    // Kleurplaat (lijntekening van scène 1) async toevoegen aan de post
    if (!is_wp_error($post_id) && $post_id && !empty($images[0]['local'])) {
        wp_schedule_single_event(time() + 180, PD_COLORING, array(array('post_id' => (int) $post_id, 'src' => (string) $images[0]['local'], 'stamp' => (string) $p['stamp'], 'title' => (string) $story['title'])));
    }

    // v0.28.0: Facebook-post voor de aflevering (link naar blog + YT-link in tekst)
    if (!is_wp_error($post_id) && $post_id) {
        wp_schedule_single_event(time() + 120, PD_FB_STORY, array(array('post_id' => (int) $post_id, 'yt' => is_string($yt) ? $yt : '')));
    }

    // bijbel laten meegroeien + archief
    $added = pd_canon_merge((array) ($story['new_characters'] ?? array()), (array) ($story['new_places'] ?? array()));
    $jobs  = pd_ref_jobs_for_added($added, $story, $images);
    if ($jobs) {
        wp_schedule_single_event(time() + 60, PD_MAKE_REFS, array($jobs));
        pd_log('Referentie-generatie ingepland voor: ' . implode(', ', wp_list_pluck($jobs, 'name')) . '.');
    }
    pd_append_story_log($ep, (string) $p['date'], $story);
    pd_set('pd_last_run', array('time' => gmdate('Y-m-d H:i:s') . 'Z', 'ep' => $ep, 'title' => $story['title'], 'video' => $final_url, 'youtube' => $yt, 'short' => is_string($short) ? $short : '', 'post_id' => is_wp_error($post_id) ? null : $post_id));
    pd_log(sprintf('Aflevering %d KLAAR: "%s" | post %s | yt %s', $ep, $story['title'], is_wp_error($post_id) ? 'FOUT' : $post_id, is_string($yt) ? $yt : 'n.v.t.'));

    // TikTok direct publiceren vóór opruimen: PULL_FROM_URL vereist een actieve URL.
    $tt_result = pd_post_tiktok($final_url, $story);
    if (is_string($tt_result) && '' !== $tt_result) {
        pd_log('TikTok live: ' . $tt_result);
        if ($post_id_int) { switch_to_blog(PD_BLOG); update_post_meta($post_id_int, '_pd_tiktok_url', $tt_result); restore_current_blog(); }
    } elseif (is_wp_error($tt_result)) {
        pd_log('TikTok mislukt: ' . $tt_result->get_error_message());
    }

    // opruimen: server slank houden. De voorlees-mp3 blijft BEWAARD voor de podcast-feed.
    pd_podcast_register($ep, $story, $p, is_wp_error($post_id) ? 0 : (int) $post_id);
    if ('' !== $yt_id && file_exists($local)) { @unlink($local); } // mp4 weg als YouTube de bron is
    pd_set('pd_pending', '');       // pas NU wissen: aflevering is veilig gepubliceerd
    pd_set('pd_finalize_lock', 0);  // lock vrijgeven
    return array('done' => true, 'ep' => $ep, 'title' => $story['title'], 'youtube' => $yt, 'post_id' => is_wp_error($post_id) ? null : $post_id);
}

/* ---- Vangnet: gedachtestreepjes uit zichtbare tekst (menselijker, minder AI) ---- */
if (!function_exists('pd_no_dash')) {
    /** Vervangt — – en los ' - ' door een komma; koppeltekens in woorden blijven staan. */
    function pd_no_dash($s) {
        if (!is_string($s) || '' === $s) { return $s; }
        $s = preg_replace('/\s*[\x{2014}\x{2013}]\s*/u', ', ', $s); // em/en-dash -> komma
        $s = preg_replace('/(\S)\s-\s(\S)/u', '$1, $2', $s);        // los ' - ' tussen woorden -> komma
        $s = preg_replace('/\s*,\s*,/u', ',', $s);                  // dubbele komma's opruimen
        $s = preg_replace('/\s+([,.;:!?])/u', '$1', $s);            // spatie vóór leesteken weg
        return $s;
    }
}
if (!function_exists('pd_clean_story')) {
    /** Ontdoet titel, samenvatting en alle scènetekst van gedachtestreepjes. */
    function pd_clean_story($story) {
        if (!is_array($story)) { return $story; }
        foreach (array('title', 'summary', 'character') as $k) {
            if (isset($story[$k])) { $story[$k] = pd_no_dash($story[$k]); }
        }
        if (!empty($story['scenes']) && is_array($story['scenes'])) {
            foreach ($story['scenes'] as $i => $sc) {
                if (isset($sc['text'])) { $story['scenes'][$i]['text'] = pd_no_dash($sc['text']); }
            }
        }
        return $story;
    }
}

/* Verwijder een wachtrij-item op titel — aangeroepen zodra fase A geslaagd is. */
function pd_queue_remove(string $title): void {
    if ('' === $title) { return; }
    $q = pd_get('pd_script_queue', '');
    $queue = is_array($q) ? $q : json_decode((string) $q, true);
    if (!is_array($queue)) { return; }
    foreach ($queue as $i => $item) {
        if (is_array($item) && trim((string) ($item['title'] ?? '')) === $title) {
            unset($queue[$i]);
            pd_set('pd_script_queue', wp_json_encode(array_values($queue), JSON_UNESCAPED_UNICODE));
            pd_log('Wachtrij-item afgerond en verwijderd: "' . $title . '".');
            return;
        }
    }
}

/* ====================================================================
 * ADMINPAGINA "Verhalen wachtrij" — blog 5 wp-admin: wachtrij bekijken,
 * verhalen bewerken/toevoegen/verwijderen/sorteren. Ruwe JSON als vangnet.
 * ==================================================================== */
function pd_queue_read(): array {
    $q = pd_get('pd_script_queue', '');
    $queue = is_array($q) ? $q : json_decode((string) $q, true);
    return is_array($queue) ? array_values($queue) : array();
}
function pd_queue_write(array $queue): void {
    pd_set('pd_script_queue', wp_json_encode(array_values($queue), JSON_UNESCAPED_UNICODE));
}

add_action('admin_menu', function () {
    if (get_current_blog_id() !== PD_BLOG) { return; }
    add_menu_page('Verhalen wachtrij', 'Verhalen wachtrij', 'manage_options', 'pd-wachtrij', 'pd_admin_queue_page', 'dashicons-book-alt', 26);
    add_submenu_page('pd-wachtrij', 'Wachtrij', 'Wachtrij', 'manage_options', 'pd-wachtrij', 'pd_admin_queue_page');
    add_submenu_page('pd-wachtrij', 'Personages & plekken', 'Personages', 'manage_options', 'pd-personages', 'pd_admin_canon_page');
    add_submenu_page('pd-wachtrij', 'Engels (EN)', 'Engels (EN)', 'manage_options', 'pd-english', 'pd_admin_english_page');
    add_submenu_page('pd-wachtrij', 'TikTok', 'TikTok', 'manage_options', 'pd-tiktok', 'pd_tt_admin_page');
});

/** Bouwt een wachtrij-item uit het formulier; behoudt onbekende velden uit $base. */
function pd_item_from_post(array $base = array()) {
    $item = $base;
    $item['title']     = sanitize_text_field(wp_unslash((string) ($_POST['pd_title'] ?? '')));
    $item['character'] = sanitize_text_field(wp_unslash((string) ($_POST['pd_character'] ?? 'Mosje')));
    $item['summary']   = sanitize_textarea_field(wp_unslash((string) ($_POST['pd_summary'] ?? '')));
    $cont = trim(sanitize_textarea_field(wp_unslash((string) ($_POST['pd_continuity'] ?? ''))));
    if ('' !== $cont) { $item['continuity'] = $cont; } else { unset($item['continuity']); }
    $date = trim((string) ($_POST['pd_date'] ?? ''));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $item['date'] = $date; } else { unset($item['date']); }
    $scenes = array();
    for ($i = 0; $i < 7; $i++) {
        $t = trim(sanitize_textarea_field(wp_unslash((string) ($_POST['pd_scene_text'][$i] ?? ''))));
        $im = trim(sanitize_textarea_field(wp_unslash((string) ($_POST['pd_scene_image'][$i] ?? ''))));
        if ('' === $t && '' === $im) { continue; }
        if ('' === $t || '' === $im) { return new WP_Error('pd_form', sprintf('Scène %d is niet compleet (tekst én tekening-beschrijving zijn verplicht).', $i + 1)); }
        $scenes[] = array('text' => $t, 'image' => $im);
    }
    $item['scenes'] = $scenes;
    if ('' === trim((string) $item['title'])) { return new WP_Error('pd_form', 'Titel is verplicht.'); }
    return $item;
}

/** Tegoed/verbruik bij de API's (10 min gecachet; ?pd_refresh=1 voor vers). */
function pd_admin_credits(): array {
    $cached = get_transient('pd_admin_credits');
    if (is_array($cached) && empty($_GET['pd_refresh'])) { return $cached; }
    $lines = array();

    // ElevenLabs: exacte tellerstand (tekens per maand)
    $el = (string) pd_key('dhs_elevenlabs_api_key');
    if ('' !== $el) {
        $r = wp_remote_get('https://api.elevenlabs.io/v1/user/subscription', array('timeout' => 15, 'headers' => array('xi-api-key' => $el)));
        $d = is_wp_error($r) ? null : json_decode(wp_remote_retrieve_body($r), true);
        if (is_array($d) && isset($d['character_limit'])) {
            $used = (int) ($d['character_count'] ?? 0); $limit = (int) $d['character_limit'];
            $left = max(0, $limit - $used);
            $eps  = (int) floor($left / 3000); // ~3000 tekens per verhaaltje van ~480 woorden
            $reset = !empty($d['next_character_count_reset_unix']) ? wp_date('j F', (int) $d['next_character_count_reset_unix']) : '?';
            $lines[] = sprintf('🎙️ <strong>ElevenLabs (stem):</strong> %s van %s tekens over (±%d verhaaltjes), vernieuwt %s.', number_format_i18n($left), number_format_i18n($limit), $eps, $reset);
        } else {
            $lines[] = '🎙️ <strong>ElevenLabs:</strong> tegoed niet opgehaald (key/verbinding?).';
        }
    }

    // OpenAI: échte cijfers via een Admin-key (optie pd_openai_admin_key, scope
    // api.usage.read vereist); anders de eigen teller (beelden + tokens geschat).
    $oa_done = false;
    $admin_key = (string) pd_get('pd_openai_admin_key');
    if ('' !== $admin_key) {
        $start = strtotime(gmdate('Y-m-01'));
        $r = wp_remote_get('https://api.openai.com/v1/organization/costs?start_time=' . $start . '&limit=31', array('timeout' => 15, 'headers' => array('Authorization' => 'Bearer ' . $admin_key)));
        if (!is_wp_error($r) && 200 === wp_remote_retrieve_response_code($r)) {
            $d = json_decode(wp_remote_retrieve_body($r), true);
            $sum = 0.0;
            foreach ((array) ($d['data'] ?? array()) as $bucket) {
                foreach ((array) ($bucket['results'] ?? array()) as $res) { $sum += (float) ($res['amount']['value'] ?? 0); }
            }
            $lines[] = sprintf('🎨 <strong>OpenAI (verhaal + tekeningen):</strong> $%.2f verbruikt deze maand (echte cijfers). Saldo en limiet: <a href="https://platform.openai.com/settings/organization/billing/overview" target="_blank">Billing</a> / <a href="https://platform.openai.com/settings/organization/limits" target="_blank">Limits</a>.', $sum);
            $oa_done = true;
        } else {
            $lines[] = '🎨 <strong>OpenAI:</strong> Admin-key geweigerd (scope api.usage.read nodig) — maak een nieuwe aan op platform.openai.com → Organization → Admin keys.';
            $oa_done = true;
        }
    }
    if (!$oa_done) {
        $c = (array) pd_get('pd_cost_month', array());
        $imgs = (int) ($c['images'] ?? 0); $eps = (int) ($c['episodes'] ?? 0); $tok = (int) ($c['story_tokens'] ?? 0);
        $est = $imgs * 0.065 + $tok / 1000000 * 12.5; // gpt-image-1 medium ≈ $0,065/beeld; gpt-4o ≈ $12,50/1M tokens gemengd
        $lines[] = sprintf('🎨 <strong>OpenAI (eigen teller %s):</strong> %d afleveringen, %d beelden ≈ $%.2f geschat. Echte cijfers? Vul hieronder een Admin-key in.', esc_html((string) ($c['month'] ?? gmdate('Y-m'))), $eps, $imgs, $est);
    }

    // Shotstack: geen saldo-API — verwijzing
    $lines[] = '🎬 <strong>Shotstack (video):</strong> pay-as-you-go, verbruik op <a href="https://dashboard.shotstack.io/" target="_blank">dashboard.shotstack.io</a>.';

    set_transient('pd_admin_credits', $lines, 600);
    return $lines;
}

function pd_admin_queue_page() {
    if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
    $notice = ''; $error = '';

    if (!empty($_POST['pd_qa']) && check_admin_referer('pd_queue_edit', 'pd_nonce')) {
        $queue = pd_queue_read();
        $qa  = sanitize_key((string) $_POST['pd_qa']);
        $idx = isset($_POST['pd_idx']) ? (int) $_POST['pd_idx'] : -1;
        if ('save' === $qa && isset($queue[$idx])) {
            $item = pd_item_from_post($queue[$idx]);
            if (is_wp_error($item)) { $error = $item->get_error_message(); }
            else { $queue[$idx] = $item; pd_queue_write($queue); $notice = '"' . esc_html($item['title']) . '" opgeslagen.'; }
        } elseif ('new' === $qa) {
            $item = pd_item_from_post();
            if (is_wp_error($item)) { $error = $item->get_error_message(); }
            else { $queue[] = $item; pd_queue_write($queue); $notice = '"' . esc_html($item['title']) . '" toegevoegd aan de wachtrij.'; }
        } elseif ('delete' === $qa && isset($queue[$idx])) {
            $t = (string) ($queue[$idx]['title'] ?? '?'); unset($queue[$idx]);
            pd_queue_write($queue); $notice = '"' . esc_html($t) . '" verwijderd.';
        } elseif (('up' === $qa || 'down' === $qa) && isset($queue[$idx])) {
            $to = ('up' === $qa) ? $idx - 1 : $idx + 1;
            if (isset($queue[$to])) { $tmp = $queue[$to]; $queue[$to] = $queue[$idx]; $queue[$idx] = $tmp; pd_queue_write($queue); $notice = 'Volgorde aangepast.'; }
        } elseif ('adminkey' === $qa) {
            $k = trim((string) wp_unslash((string) ($_POST['pd_admin_key'] ?? '')));
            pd_set('pd_openai_admin_key', $k);
            delete_transient('pd_admin_credits');
            $notice = '' !== $k ? 'OpenAI Admin-key opgeslagen.' : 'OpenAI Admin-key gewist (terug naar eigen teller).';
        } elseif ('raw' === $qa) {
            $raw = trim((string) wp_unslash((string) ($_POST['pd_raw'] ?? '')));
            $dec = json_decode($raw, true);
            if (!is_array($dec)) { $error = 'Ongeldige JSON — niets opgeslagen.'; }
            else { pd_queue_write($dec); $notice = 'Ruwe JSON opgeslagen (' . count($dec) . ' items).'; }
        }
    }

    $queue = pd_queue_read();
    $pending = pd_get('pd_pending');
    $last = pd_get('pd_last_run');

    $field = function (string $label, string $html) {
        echo '<p style="margin:8px 0"><label style="display:block;font-weight:600;margin-bottom:2px">' . esc_html($label) . '</label>' . $html . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
    };
    $item_form = function (?array $it, int $idx) use ($field) {
        $is_new = (null === $it); $it = (array) $it;
        echo '<form method="post" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 16px 12px;margin:0">';
        wp_nonce_field('pd_queue_edit', 'pd_nonce');
        echo '<input type="hidden" name="pd_idx" value="' . (int) $idx . '">';
        $field('Titel', '<input type="text" name="pd_title" class="regular-text" style="width:100%" value="' . esc_attr((string) ($it['title'] ?? '')) . '">');
        echo '<div style="display:flex;gap:16px;flex-wrap:wrap">';
        $field('Hoofdpersoon', '<input type="text" name="pd_character" value="' . esc_attr((string) ($it['character'] ?? 'Mosje')) . '">');
        $field('Alleen op datum (leeg = eerstvolgende dag)', '<input type="date" name="pd_date" value="' . esc_attr((string) ($it['date'] ?? '')) . '">');
        echo '</div>';
        $field('Samenvatting (1-2 zinnen, voor het archief en YouTube)', '<textarea name="pd_summary" rows="2" style="width:100%">' . esc_textarea((string) ($it['summary'] ?? '')) . '</textarea>');
        $field('Continuity — vaste details die in ALLE tekeningen gelijk blijven (Engels: kleur+aantal voorwerpen, seizoen, licht)', '<textarea name="pd_continuity" rows="2" style="width:100%" placeholder="one single small red ball; fresh green leaves; soft evening light">' . esc_textarea((string) ($it['continuity'] ?? '')) . '</textarea>');
        $scenes = is_array($it['scenes'] ?? null) ? array_values($it['scenes']) : array();
        for ($i = 0; $i < 7; $i++) {
            echo '<fieldset style="border:1px solid #e5e5e5;border-radius:6px;padding:8px 12px;margin:10px 0"><legend style="font-weight:600">Scène ' . ($i + 1) . '</legend>';
            $field('Verhaaltekst (NL — Nijntje-stijl, max 8 woorden per zin)', '<textarea name="pd_scene_text[' . $i . ']" rows="4" style="width:100%">' . esc_textarea((string) ($scenes[$i]['text'] ?? '')) . '</textarea>');
            $field('Tekening (korte Engelse beschrijving, geen tekst in beeld)', '<textarea name="pd_scene_image[' . $i . ']" rows="2" style="width:100%">' . esc_textarea((string) ($scenes[$i]['image'] ?? '')) . '</textarea>');
            echo '</fieldset>';
        }
        if ($is_new) {
            echo '<p><button class="button button-primary" name="pd_qa" value="new">➕ Toevoegen aan wachtrij</button></p>';
        } else {
            echo '<p style="display:flex;gap:8px;align-items:center">'
                . '<button class="button button-primary" name="pd_qa" value="save">Opslaan</button>'
                . '<button class="button" name="pd_qa" value="up" title="Eerder afspelen">▲</button>'
                . '<button class="button" name="pd_qa" value="down" title="Later afspelen">▼</button>'
                . '<button class="button button-link-delete" name="pd_qa" value="delete" onclick="return confirm(\'Dit verhaal uit de wachtrij verwijderen?\')">Verwijderen</button></p>';
        }
        echo '</form>';
    };

    echo '<div class="wrap"><h1>📖 Verhalen wachtrij</h1>';
    if ($notice) { echo '<div class="notice notice-success is-dismissible"><p>' . $notice . '</p></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput
    if ($error) { echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>'; }

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;margin:12px 0;max-width:900px">';
    echo '<p style="margin:4px 0"><strong>Hoe werkt het?</strong> Elke dag om 17:00 speelt het bovenste verhaal zonder datum (of mét de datum van vandaag). Is de wachtrij leeg, dan verzint de AI zelf een verhaal. Handmatig direct starten: <code>' . esc_html('https://www.praatdeurtje.nl/?pd_run=pd-test-Mosje42') . '</code></p>';
    if (is_array($last) && !empty($last['title'])) {
        echo '<p style="margin:4px 0">Laatste aflevering: <strong>' . esc_html((string) $last['title']) . '</strong> (' . esc_html((string) ($last['time'] ?? '')) . ')' . (!empty($last['youtube']) ? ' — <a href="https://' . esc_attr((string) $last['youtube']) . '" target="_blank">' . esc_html((string) $last['youtube']) . '</a>' : '') . '</p>';
    }
    echo '<p style="margin:4px 0">Openstaande render: ' . (is_array($pending) && !empty($pending['render_id']) ? '<strong>bezig</strong> (' . esc_html((string) ($pending['story']['title'] ?? '?')) . ')' : 'geen') . '</p>';
    echo '<hr style="margin:10px 0;border:0;border-top:1px solid #e5e5e5"><p style="margin:4px 0;font-weight:600">Tegoed &amp; verbruik <a href="' . esc_url(add_query_arg('pd_refresh', '1')) . '" style="font-weight:400;font-size:12px">(ververs)</a></p>';
    foreach (pd_admin_credits() as $l) { echo '<p style="margin:4px 0">' . $l . '</p>'; } // phpcs:ignore WordPress.Security.EscapeOutput
    echo '<form method="post" style="margin:6px 0;display:flex;gap:8px;align-items:center">';
    wp_nonce_field('pd_queue_edit', 'pd_nonce');
    echo '<input type="password" name="pd_admin_key" placeholder="OpenAI Admin-key (sk-admin-… of sk-svcacct-…, scope Usage: Read)" style="width:420px" value="' . esc_attr((string) pd_get('pd_openai_admin_key')) . '">';
    echo '<button class="button" name="pd_qa" value="adminkey">Opslaan</button></form>';
    echo '</div>';

    echo '<h2>' . count($queue) . ' verhaal/verhalen in de wachtrij</h2><div style="max-width:900px">';
    if (!$queue) { echo '<p><em>De wachtrij is leeg — de AI verzint elke dag zelf een verhaal tot je hier iets toevoegt.</em></p>'; }
    foreach ($queue as $i => $it) {
        $badge = !empty($it['date']) ? ' <span style="background:#dba617;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px">' . esc_html((string) $it['date']) . '</span>' : '';
        $cont  = !empty($it['continuity']) ? ' <span style="background:#00a32a;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px">continuity ✓</span>' : ' <span style="background:#d63638;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px">geen continuity</span>';
        echo '<details style="margin:8px 0"><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px"><strong>' . ($i + 1) . '. ' . esc_html((string) ($it['title'] ?? '(zonder titel)')) . '</strong>' . $badge . $cont . '</summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
        $item_form((array) $it, (int) $i);
        echo '</details>';
    }
    echo '</div>';

    echo '<h2 style="margin-top:24px">➕ Nieuw verhaal</h2><div style="max-width:900px"><details><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f0f6fc;border:1px solid #dcdcde;border-radius:8px">Nieuw verhaal schrijven (5–7 scènes)</summary>';
    $item_form(null, -1);
    echo '</details></div>';

    echo '<h2 style="margin-top:24px">⚙️ Ruwe JSON (vangnet)</h2><div style="max-width:900px"><details><summary style="cursor:pointer;font-size:13px;padding:8px 12px;background:#fcf9e8;border:1px solid #dcdcde;border-radius:8px">Hele wachtrij als JSON bekijken/bewerken</summary>';
    echo '<form method="post" style="margin-top:8px">';
    wp_nonce_field('pd_queue_edit', 'pd_nonce');
    echo '<textarea name="pd_raw" rows="18" style="width:100%;font-family:monospace">' . esc_textarea((string) wp_json_encode($queue, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . '</textarea>';
    echo '<p><button class="button" name="pd_qa" value="raw" onclick="return confirm(\'Hele wachtrij overschrijven met deze JSON?\')">Ruwe JSON opslaan</button></p></form></details></div>';
    echo '</div>';
}

/* ====================================================================
 * ADMINPAGINA "Personages & plekken" — de canon bekijken/bewerken en
 * referentiebeelden vervangen (eigen afbeelding uploaden).
 * ==================================================================== */

/** Slaat een geüploade afbeelding op als ref-<slug>-1.png (of ref-plek-). Oude ref wordt old-ref-...png. */
function pd_admin_save_ref(string $kind, string $slug) {
    if (empty($_FILES['pd_ref']['tmp_name']) || !is_uploaded_file($_FILES['pd_ref']['tmp_name'])) { return ''; }
    if (!empty($_FILES['pd_ref']['error'])) { return new WP_Error('pd_upload', 'Upload mislukt (code ' . (int) $_FILES['pd_ref']['error'] . ').'); }
    $info = @getimagesize($_FILES['pd_ref']['tmp_name']);
    if (!$info || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP), true)) { return new WP_Error('pd_upload', 'Alleen PNG, JPG of WebP.'); }
    $im = @imagecreatefromstring((string) file_get_contents($_FILES['pd_ref']['tmp_name']));
    if (!$im) { return new WP_Error('pd_upload', 'Afbeelding kon niet gelezen worden.'); }
    if (!is_dir(PD_DIR)) { wp_mkdir_p(PD_DIR); }
    $prefix = ('place' === $kind) ? 'ref-plek-' : 'ref-';
    foreach (glob(PD_DIR . '/' . $prefix . $slug . '-*.png') ?: array() as $old) {
        @rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old)); // backup, valt buiten het ref-glob
    }
    if ('place' === $kind) { // v0.18.1: seizoensvarianten zijn afgeleid van de basis-ref — bij een nieuwe basis mee naar de backup, dan worden ze vers afgeleid
        foreach (glob(PD_DIR . '/ref-plekseizoen-*-' . $slug . '-*.png') ?: array() as $old) {
            @rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old));
        }
    }
    if ('mosje' === $slug) { foreach (glob(PD_DIR . '/mosje-ref-*.png') ?: array() as $old) { @rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old)); } }
    $file = PD_DIR . '/' . $prefix . $slug . '-1.png';
    imagepng($im, $file, 6);
    imagedestroy($im);
    pd_log('Referentiebeeld vervangen via admin: ' . basename($file) . '.');
    return basename($file);
}

/** Slaat een geüpload seizoensbeeld op als ref-plekseizoen-<seizoen>-<slug>-1.png (v0.19). Oude variant wordt old-...png. */
function pd_admin_save_seizoensref(string $slug, string $sz) {
    $field = 'pd_szref_' . $sz;
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) { return ''; }
    if (!empty($_FILES[$field]['error'])) { return new WP_Error('pd_upload', ucfirst($sz) . '-upload mislukt (code ' . (int) $_FILES[$field]['error'] . ').'); }
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$info || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP), true)) { return new WP_Error('pd_upload', 'Alleen PNG, JPG of WebP (' . $sz . ').'); }
    $im = @imagecreatefromstring((string) file_get_contents($_FILES[$field]['tmp_name']));
    if (!$im) { return new WP_Error('pd_upload', ucfirst($sz) . '-beeld kon niet gelezen worden.'); }
    if (!is_dir(PD_DIR)) { wp_mkdir_p(PD_DIR); }
    foreach (glob(PD_DIR . '/ref-plekseizoen-' . $sz . '-' . $slug . '-*.png') ?: array() as $old) {
        @rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old));
    }
    $file = PD_DIR . '/ref-plekseizoen-' . $sz . '-' . $slug . '-1.png';
    imagepng($im, $file, 6);
    imagedestroy($im);
    pd_log('Seizoensref vervangen via admin: ' . basename($file) . '.');
    return basename($file);
}

/** Slaat een geüpload poseblad op als poseblad-<slug>-1.png (v0.17.1). Oude poseblad wordt old-...png. */
function pd_admin_save_poseblad(string $slug) {
    if (empty($_FILES['pd_poseblad']['tmp_name']) || !is_uploaded_file($_FILES['pd_poseblad']['tmp_name'])) { return ''; }
    if (!empty($_FILES['pd_poseblad']['error'])) { return new WP_Error('pd_upload', 'Poseblad-upload mislukt (code ' . (int) $_FILES['pd_poseblad']['error'] . ').'); }
    $info = @getimagesize($_FILES['pd_poseblad']['tmp_name']);
    if (!$info || !in_array($info[2], array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP), true)) { return new WP_Error('pd_upload', 'Alleen PNG, JPG of WebP.'); }
    $im = @imagecreatefromstring((string) file_get_contents($_FILES['pd_poseblad']['tmp_name']));
    if (!$im) { return new WP_Error('pd_upload', 'Poseblad kon niet gelezen worden.'); }
    if (!is_dir(PD_DIR)) { wp_mkdir_p(PD_DIR); }
    foreach (glob(PD_DIR . '/poseblad-' . $slug . '-*.png') ?: array() as $old) {
        @rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old)); // backup, valt buiten het poseblad-glob
    }
    $file = PD_DIR . '/poseblad-' . $slug . '-1.png';
    imagepng($im, $file, 6);
    imagedestroy($im);
    pd_log('Poseblad vervangen via admin: ' . basename($file) . '.');
    return basename($file);
}

function pd_admin_canon_page() {
    if (!current_user_can('manage_options')) { wp_die('Geen toegang.'); }
    $notice = ''; $error = '';

    if (!empty($_POST['pd_ca']) && check_admin_referer('pd_canon_edit', 'pd_nonce')) {
        $canon = pd_canon();
        $ca  = sanitize_key((string) $_POST['pd_ca']);
        $idx = isset($_POST['pd_idx']) ? (int) $_POST['pd_idx'] : -1;
        $txt = function (string $k): string { return trim(sanitize_textarea_field(wp_unslash((string) ($_POST[$k] ?? '')))); };
        if ('world' === $ca) {
            $canon['world'] = $txt('pd_world');
            pd_canon_save($canon); $notice = 'Wereldbeschrijving opgeslagen.';
        } elseif ('save_char' === $ca || 'new_char' === $ca) {
            $ch = ('save_char' === $ca && isset($canon['characters'][$idx])) ? (array) $canon['characters'][$idx] : array();
            $ch['name']          = $txt('pd_name');
            $ch['uiterlijk']     = $txt('pd_uiterlijk');
            $ch['uiterlijk_en']  = $txt('pd_uiterlijk_en');
            $ch['woont']         = $txt('pd_woont');
            $ch['eigenschappen'] = $txt('pd_eigenschappen');
            $props = $txt('pd_props');    if ('' !== $props) { $ch['props'] = $props; }    else { unset($ch['props']); }
            $verb  = $txt('pd_verboden'); if ('' !== $verb)  { $ch['verboden'] = $verb; }  else { unset($ch['verboden']); }
            $kw = array_values(array_filter(array_map('trim', explode(',', $txt('pd_keywords')))));
            if ($kw) { $ch['keywords'] = $kw; } else { unset($ch['keywords']); }
            if ('' === $ch['name']) { $error = 'Naam is verplicht.'; }
            else {
                if ('new_char' === $ca) { $canon['characters'][] = $ch; } else { $canon['characters'][$idx] = $ch; }
                pd_canon_save($canon);
                $up = pd_admin_save_ref('character', pd_canon_slug($ch['name']));
                if (is_wp_error($up)) { $error = $up->get_error_message(); }
                $pb = pd_admin_save_poseblad(pd_canon_slug($ch['name']));
                if (is_wp_error($pb)) { $error = trim($error . ' ' . $pb->get_error_message()); }
                $notice = '"' . esc_html($ch['name']) . '" opgeslagen'
                    . (is_string($up) && '' !== $up ? ' + nieuw referentiebeeld (' . esc_html($up) . ')' : '')
                    . (is_string($pb) && '' !== $pb ? ' + nieuw poseblad (' . esc_html($pb) . ')' : '') . '.';
            }
        } elseif ('delete_poseblad' === $ca && isset($canon['characters'][$idx])) {
            $slug = pd_canon_slug((string) ($canon['characters'][$idx]['name'] ?? ''));
            $n = 0;
            foreach (glob(PD_DIR . '/poseblad-' . $slug . '-*.png') ?: array() as $old) {
                if (@rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old))) { $n++; }
            }
            if ($n) { pd_log('Poseblad verwijderd via admin: ' . $slug . '.'); }
            $notice = $n ? 'Poseblad verwijderd (backup bewaard als old-...png).' : 'Geen poseblad gevonden om te verwijderen.';
        } elseif ('delete_char' === $ca && isset($canon['characters'][$idx])) {
            $n = (string) ($canon['characters'][$idx]['name'] ?? '?');
            unset($canon['characters'][$idx]);
            $canon['characters'] = array_values($canon['characters']);
            pd_canon_save($canon); $notice = '"' . esc_html($n) . '" verwijderd uit de canon (referentiebeeld blijft staan).';
        } elseif ('save_place' === $ca || 'new_place' === $ca) {
            $pl = ('save_place' === $ca && isset($canon['places'][$idx])) ? (array) $canon['places'][$idx] : array();
            $pl['name']            = $txt('pd_name');
            $pl['beschrijving']    = $txt('pd_beschrijving');
            $pl['beschrijving_en'] = $txt('pd_beschrijving_en');
            $binnen = $txt('pd_binnen_en'); if ('' !== $binnen) { $pl['binnen_en'] = $binnen; } else { unset($pl['binnen_en']); }
            $props = $txt('pd_props');    if ('' !== $props) { $pl['props'] = $props; }    else { unset($pl['props']); }
            $verb  = $txt('pd_verboden'); if ('' !== $verb)  { $pl['verboden'] = $verb; }  else { unset($pl['verboden']); }
            if ('' === $pl['name']) { $error = 'Naam is verplicht.'; }
            else {
                if ('new_place' === $ca) { $canon['places'][] = $pl; } else { $canon['places'][$idx] = $pl; }
                pd_canon_save($canon);
                $up = pd_admin_save_ref('place', pd_slugify($pl['name']));
                if (is_wp_error($up)) { $error = $up->get_error_message(); }
                $sz_uploaded = array();
                foreach (array('lente', 'herfst', 'winter') as $sz) { // v0.19: eigen seizoensbeelden uploaden
                    $szr = pd_admin_save_seizoensref(pd_slugify($pl['name']), $sz);
                    if (is_wp_error($szr)) { $error = trim($error . ' ' . $szr->get_error_message()); }
                    elseif ('' !== $szr) { $sz_uploaded[] = $sz; }
                }
                $notice = '"' . esc_html($pl['name']) . '" opgeslagen'
                    . (is_string($up) && '' !== $up ? ' + nieuw referentiebeeld' : '')
                    . ($sz_uploaded ? ' + seizoensbeeld(en): ' . esc_html(implode(', ', $sz_uploaded)) : '') . '.';
            }
        } elseif (('make_season' === $ca || 'delete_season' === $ca) && isset($canon['places'][$idx])) {
            $slug = pd_slugify((string) ($canon['places'][$idx]['name'] ?? ''));
            $sz   = sanitize_key((string) ($_POST['pd_seizoen'] ?? ''));
            if (!in_array($sz, array('lente', 'herfst', 'winter'), true)) {
                $error = 'Onbekend seizoen.';
            } elseif ('delete_season' === $ca) {
                $n = 0;
                foreach (glob(PD_DIR . '/ref-plekseizoen-' . $sz . '-' . $slug . '-*.png') ?: array() as $old) {
                    if (@rename($old, PD_DIR . '/old-' . gmdate('Ymd-His') . '-' . basename($old))) { $n++; }
                }
                $notice = $n ? ucfirst($sz) . '-variant verwijderd (backup bewaard) — hij wordt opnieuw gemaakt zodra nodig, of via "nu alvast maken".' : 'Geen variant gevonden.';
            } else {
                ignore_user_abort(true);
                $f = pd_seizoens_plekref($slug, $sz);
                if ('' !== $f) { $notice = ucfirst($sz) . '-variant gemaakt voor "' . esc_html((string) ($canon['places'][$idx]['name'] ?? '')) . '" — staat hieronder bij de plek.'; }
                else { $error = 'Variant maken mislukte — is er een basis-ref en een OpenAI-key? (zie ook het logboek)'; }
            }
        } elseif ('delete_place' === $ca && isset($canon['places'][$idx])) {
            $n = (string) ($canon['places'][$idx]['name'] ?? '?');
            unset($canon['places'][$idx]);
            $canon['places'] = array_values($canon['places']);
            pd_canon_save($canon); $notice = '"' . esc_html($n) . '" verwijderd.';
        }
    }

    $canon = pd_canon();
    $field = function (string $label, string $html) {
        echo '<p style="margin:8px 0"><label style="display:block;font-weight:600;margin-bottom:2px">' . esc_html($label) . '</label>' . $html . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
    };
    $ref_img = function (string $kind, string $slug): string {
        $f = pd_ref_file($kind, $slug);
        if ('' === $f) { return '<em>nog geen referentiebeeld</em>'; }
        $url = PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f);
        return '<a href="' . esc_url($url) . '" target="_blank"><img src="' . esc_url($url) . '" style="max-width:260px;height:auto;border-radius:10px;border:1px solid #dcdcde" alt=""></a>';
    };

    echo '<div class="wrap"><h1>🧸 Personages & plekken (de vaste wereld)</h1>';
    if ($notice) { echo '<div class="notice notice-success is-dismissible"><p>' . $notice . '</p></div>'; } // phpcs:ignore WordPress.Security.EscapeOutput
    if ($error) { echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>'; }

    echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 16px;margin:12px 0;max-width:900px">'
        . '<p style="margin:4px 0"><strong>Zo werkt het:</strong> het <em>referentiebeeld</em> bepaalt hoe een personage getekend wordt; de Engelse beschrijving (uiterlijk EN) helpt de tekenaar mee. Upload een schone afbeelding: alléén het figuur, lichte/rustige achtergrond, <strong>géén tekst of bordjes</strong> in beeld (tekst lekt door in de tekeningen).</p>'
        . '<p style="margin:4px 0">Mosje, Kwakkel en de nachtbloem hebben daarnaast een vaste basislook in de code — bij hen telt vooral het referentiebeeld.</p>'
        . '<p style="margin:4px 0">Een <em>poseblad</em> (optioneel) is één blad met ±6 houdingen van hetzelfde figuur (staan, lopen links/rechts, hurken, zitten, wijzen). Het leert de tekenaar dat het figuur mag bewegen: identiteit vast, pose vrij. Het blad gaat automatisch mee wanneer dit personage de <strong>hoofdrol</strong> in een scène heeft en er ref-ruimte over is. Zelfde regels als de ref: lichte achtergrond, géén tekst of labels.</p></div>';

    // De kaart van het bos (gegenereerd; bron uploads/praatdeurtje-videos/wereldkaart.png)
    $kaart = PD_DIR . '/wereldkaart.png';
    if (file_exists($kaart)) {
        echo '<h2>🗺️ Kaart van het Praatdeurtjesbos</h2><div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin-bottom:8px">';
        echo '<a href="' . esc_url(PD_URL_BASE . 'wereldkaart.png?v=' . filemtime($kaart)) . '" target="_blank"><img src="' . esc_url(PD_URL_BASE . 'wereldkaart.png?v=' . filemtime($kaart)) . '" style="width:100%;height:auto;border-radius:10px" alt="Kaart van het Praatdeurtjesbos"></a>';
        echo '<p style="margin:8px 0 2px;color:#646970;font-size:12px">Midden: de open plek met de nachtbloem en de paddenstoelenkring eromheen · linksboven: de grote notenboom en Mosjes boomhuisje · rechts: het meertje met Kwakkels beschutte rietnestje aan de oever en het nestgebied in het riet · onderaan het kabouterpaadje: Belles hondenhuisje en Happy\'s blauwe kattendeurtje als buren, met Roosjes hartjesdeurtje en Bloempjes holletje ernaast · rechtsboven: de boerderij met moestuin. De ligging staat ook in de wereldbeschrijving, zodat de verhalen kloppen.</p></div>';
    }

    // Het web: alle koppelingen in één oogopslag (personage -> plek -> spulletjes)
    echo '<h2>🕸️ Het web — wie hoort waar bij wat</h2><div style="max-width:1100px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin-bottom:8px">';
    $chip = function (string $txt, string $kleur): string {
        return '<span style="display:inline-block;background:' . $kleur . ';border-radius:10px;padding:2px 10px;font-size:12px;margin:2px 4px 2px 0">' . esc_html($txt) . '</span>';
    };
    $place_names = array();
    foreach ((array) $canon['places'] as $pl) { if (!empty($pl['name'])) { $place_names[] = (string) $pl['name']; } }
    foreach ((array) $canon['characters'] as $ch) {
        if (empty($ch['name'])) { continue; }
        $slug = pd_canon_slug((string) $ch['name']);
        $f = pd_ref_file('character', $slug);
        $thumb = '' !== $f ? '<img src="' . esc_url(PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f)) . '" style="height:52px;width:52px;object-fit:cover;border-radius:50%;border:2px solid #e7e0d6" alt="">' : '<span style="display:inline-block;height:52px;width:52px;border-radius:50%;background:#f0ede6;text-align:center;line-height:52px">🧸</span>';
        // woont-tekst: plekken uit de plekkenlijst die erin voorkomen worden een chip
        $woont = trim((string) ($ch['woont'] ?? ''));
        $woont_html = '' !== $woont ? esc_html($woont) : '<em>?</em>';
        foreach ($place_names as $pn) {
            $bare = preg_replace('/^(de|het|een)\s+/iu', '', $pn);
            if ('' !== $bare && false !== mb_stripos($woont, $bare, 0, 'UTF-8')) {
                $woont_html .= ' ' . $chip('📍 ' . $pn, '#e7f3e7');
                break;
            }
        }
        echo '<div style="display:flex;gap:14px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f0ede6">';
        echo '<div style="flex:0 0 52px">' . $thumb . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        echo '<div style="flex:1;min-width:0"><strong style="font-size:14px">' . esc_html((string) $ch['name']) . '</strong>';
        echo '<div style="margin:3px 0;color:#50575e">🏠 woont: ' . $woont_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
        $regels = '';
        if (!empty($ch['props']))    { $regels .= $chip('🎒 ' . (string) $ch['props'], '#fdf3e0'); }
        if (!empty($ch['verboden'])) { $regels .= $chip('🚫 ' . (string) $ch['verboden'], '#fbeaea'); }
        if ('' === $regels)          { $regels = $chip('nog geen eigen spulletjes of verboden ingevuld', '#f0f0f1'); }
        echo '<div>' . $regels . '</div></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '<p style="margin:10px 0 2px;color:#646970;font-size:12px">Zo gebruikt de tekenaar dit web: staat een personage in een scène, dan gaan zijn referentiebeeld + spulletjes-regels + verboden automatisch mee. Spulletjes verschijnen alléén als de eigenaar in beeld is (vriendjes mogen er dan wel mee spelen). Invullen/aanpassen doe je hieronder per personage of plek.</p>';
    echo '</div>';

    // Wereld
    echo '<h2>🌳 De wereld</h2><div style="max-width:900px"><form method="post" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 16px 12px">';
    wp_nonce_field('pd_canon_edit', 'pd_nonce');
    $field('Beschrijving van het Praatdeurtjesbos', '<textarea name="pd_world" rows="3" style="width:100%">' . esc_textarea((string) ($canon['world'] ?? '')) . '</textarea>');
    echo '<p><button class="button button-primary" name="pd_ca" value="world">Opslaan</button></p></form></div>';

    // Personages
    $char_form = function (?array $ch, int $idx) use ($field, $ref_img) {
        $is_new = (null === $ch); $ch = (array) $ch;
        $slug = $is_new ? '' : pd_canon_slug((string) ($ch['name'] ?? ''));
        echo '<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 16px 12px;margin:0">';
        wp_nonce_field('pd_canon_edit', 'pd_nonce');
        echo '<input type="hidden" name="pd_idx" value="' . (int) $idx . '">';
        echo '<div style="display:flex;gap:20px;flex-wrap:wrap"><div style="flex:0 0 270px">';
        echo '<p style="margin:8px 0;font-weight:600">Referentiebeeld</p>';
        echo $is_new ? '<p><em>uploaden kan direct hieronder</em></p>' : $ref_img('character', $slug); // phpcs:ignore WordPress.Security.EscapeOutput
        $field('Nieuw referentiebeeld uploaden (PNG/JPG, vervangt het huidige)', '<input type="file" name="pd_ref" accept="image/png,image/jpeg,image/webp">');
        // v0.17.1: poseblad — één blad met ±6 houdingen van hetzelfde figuur; gaat mee
        // als dit personage de hoofdrol heeft en er ref-ruimte is (max 3 refs per beeld).
        echo '<p style="margin:16px 0 8px;font-weight:600;border-top:1px solid #f0ede6;padding-top:12px">Poseblad (optioneel)</p>';
        $pb_files = ($is_new || '' === $slug) ? array() : (glob(PD_DIR . '/poseblad-' . $slug . '-*.png') ?: array());
        sort($pb_files);
        if ($pb_files) {
            $pb_url = PD_URL_BASE . rawurlencode(basename($pb_files[0])) . '?v=' . filemtime($pb_files[0]);
            echo '<a href="' . esc_url($pb_url) . '" target="_blank"><img src="' . esc_url($pb_url) . '" style="max-width:260px;height:auto;border-radius:10px;border:1px solid #dcdcde" alt=""></a>';
            echo '<p><button class="button button-link-delete" name="pd_ca" value="delete_poseblad" onclick="return confirm(\'Het poseblad van dit personage verwijderen? (er blijft een old-backup staan)\')">Poseblad verwijderen</button></p>';
        } else {
            echo '<p><em>nog geen poseblad</em></p>';
        }
        $field('Nieuw poseblad uploaden (PNG/JPG — ±6 houdingen van hetzelfde figuur op één blad, lichte rustige achtergrond, géén tekst of labels)', '<input type="file" name="pd_poseblad" accept="image/png,image/jpeg,image/webp">');
        echo '</div><div style="flex:1;min-width:320px">';
        $field('Naam', '<input type="text" name="pd_name" style="width:100%" value="' . esc_attr((string) ($ch['name'] ?? '')) . '">');
        $field('Uiterlijk (NL — gebruikt de verhalenschrijver)', '<textarea name="pd_uiterlijk" rows="3" style="width:100%">' . esc_textarea((string) ($ch['uiterlijk'] ?? '')) . '</textarea>');
        $field('Uiterlijk (EN — gebruikt de tekenaar; wees precies: vlekken, kleuren, vachtlengte)', '<textarea name="pd_uiterlijk_en" rows="3" style="width:100%">' . esc_textarea((string) ($ch['uiterlijk_en'] ?? '')) . '</textarea>');
        $field('Woont', '<input type="text" name="pd_woont" style="width:100%" value="' . esc_attr((string) ($ch['woont'] ?? '')) . '">');
        $field('Eigenschappen (karakter, voor de verhalen)', '<textarea name="pd_eigenschappen" rows="2" style="width:100%">' . esc_textarea((string) ($ch['eigenschappen'] ?? '')) . '</textarea>');
        $field('Eigen spulletjes / props (EN — alleen in beeld als dit personage erbij is)', '<textarea name="pd_props" rows="2" style="width:100%" placeholder="one small bright red ball (Belle\'s ball; only in scenes where Belle is present; exactly one; friends may play with it when Belle is there)">' . esc_textarea((string) ($ch['props'] ?? '')) . '</textarea>');
        $field('Verboden (EN — wat NOOIT getekend mag worden bij dit personage)', '<textarea name="pd_verboden" rows="2" style="width:100%" placeholder="two balls; brown fur; human-like body">' . esc_textarea((string) ($ch['verboden'] ?? '')) . '</textarea>');
        $field('Herkenningswoorden (optioneel, komma-gescheiden — waaraan een scène dit personage herkent)', '<input type="text" name="pd_keywords" style="width:100%" value="' . esc_attr(implode(', ', (array) ($ch['keywords'] ?? array()))) . '">');
        if ($is_new) {
            echo '<p><button class="button button-primary" name="pd_ca" value="new_char">➕ Personage toevoegen</button></p>';
        } else {
            echo '<p style="display:flex;gap:8px"><button class="button button-primary" name="pd_ca" value="save_char">Opslaan</button>'
                . '<button class="button button-link-delete" name="pd_ca" value="delete_char" onclick="return confirm(\'Dit personage uit de canon verwijderen?\')">Verwijderen</button></p>';
        }
        echo '</div></div></form>';
    };
    echo '<h2 style="margin-top:24px">🧸 Personages (' . count((array) $canon['characters']) . ')</h2><div style="max-width:1100px">';
    foreach ((array) $canon['characters'] as $i => $ch) {
        $slug = pd_canon_slug((string) ($ch['name'] ?? ''));
        $f = pd_ref_file('character', $slug);
        $thumb = '' !== $f ? '<img src="' . esc_url(PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f)) . '" style="height:44px;width:auto;border-radius:6px;vertical-align:middle;margin-right:10px" alt="">' : '';
        echo '<details style="margin:8px 0"><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px">' . $thumb . '<strong>' . esc_html((string) ($ch['name'] ?? '?')) . '</strong> <span style="color:#646970">— ' . esc_html(mb_substr((string) ($ch['eigenschappen'] ?? ''), 0, 80, 'UTF-8')) . '</span></summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
        $char_form((array) $ch, (int) $i);
        echo '</details>';
    }
    echo '<details style="margin:14px 0"><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f0f6fc;border:1px solid #dcdcde;border-radius:8px"><strong>➕ Nieuw personage</strong></summary>';
    $char_form(null, -1);
    echo '</details></div>';

    // Plekken
    $place_form = function (?array $pl, int $idx) use ($field, $ref_img) {
        $is_new = (null === $pl); $pl = (array) $pl;
        $slug = $is_new ? '' : pd_slugify((string) ($pl['name'] ?? ''));
        echo '<form method="post" enctype="multipart/form-data" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 16px 12px;margin:0">';
        wp_nonce_field('pd_canon_edit', 'pd_nonce');
        echo '<input type="hidden" name="pd_idx" value="' . (int) $idx . '">';
        echo '<div style="display:flex;gap:20px;flex-wrap:wrap"><div style="flex:0 0 270px">';
        echo '<p style="margin:8px 0;font-weight:600">Referentiebeeld (optioneel)</p>';
        echo $is_new ? '<p><em>uploaden kan direct hieronder</em></p>' : $ref_img('place', $slug); // phpcs:ignore WordPress.Security.EscapeOutput
        $field('Nieuw referentiebeeld uploaden', '<input type="file" name="pd_ref" accept="image/png,image/jpeg,image/webp">');
        // v0.19: seizoenen-strip — per seizoen het beeld + ingangsdatum, met vooruit maken,
        // verwijderen en eigen upload (jouw upload wint altijd; wisselt automatisch op de datum).
        if (!$is_new && '' !== $slug) {
            $huidig    = pd_seizoen();
            $seizoenen = array('lente' => '1 maart', 'zomer' => '1 juni', 'herfst' => '1 september', 'winter' => '1 december');
            echo '<p style="margin:14px 0 4px;font-weight:600">Seizoenen <span style="font-weight:400;color:#646970;font-size:12px">(beeld wisselt automatisch op de ingangsdatum)</span></p>';
            echo '<div style="display:flex;gap:8px;flex-wrap:wrap">';
            foreach ($seizoenen as $sz => $vanaf) {
                if ('zomer' === $sz) {
                    $f = pd_ref_file('place', $slug);
                } else {
                    $sfiles = glob(PD_DIR . '/ref-plekseizoen-' . $sz . '-' . $slug . '-*.png') ?: array();
                    sort($sfiles);
                    $f = $sfiles[0] ?? '';
                }
                echo '<div style="flex:0 0 128px;text-align:center;background:' . ($sz === $huidig ? '#eef5ef' : '#f6f7f7') . ';border:1px solid ' . ($sz === $huidig ? '#9bbf9b' : '#dcdcde') . ';border-radius:8px;padding:6px">';
                echo '<div style="font-weight:600;font-size:12px">' . esc_html(ucfirst($sz)) . ($sz === $huidig ? ' ✓ nu actief' : '') . '</div>';
                echo '<div style="color:#646970;font-size:11px;margin-bottom:4px">vanaf ' . esc_html($vanaf) . '</div>';
                if ('' !== $f && file_exists($f)) {
                    $su = PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f);
                    echo '<a href="' . esc_url($su) . '" target="_blank"><img src="' . esc_url($su) . '" style="width:100%;height:76px;object-fit:cover;border-radius:6px" alt="' . esc_attr($sz) . '"></a>';
                    if ('zomer' === $sz) {
                        echo '<div style="color:#646970;font-size:11px;margin-top:2px">= basis-ref hierboven</div>';
                    } else {
                        echo '<button class="button button-small button-link-delete" name="pd_ca" value="delete_season" onclick="this.form.pd_seizoen.value=\'' . esc_attr($sz) . '\';return confirm(\'Deze ' . esc_attr($sz) . '-variant verwijderen? (backup blijft bewaard)\')" style="margin-top:4px">verwijderen</button>';
                    }
                } elseif ('zomer' === $sz) {
                    echo '<div style="color:#646970;font-size:11px;padding:24px 0">nog geen basis-ref</div>';
                } else {
                    echo '<div style="color:#646970;font-size:11px;padding:6px 0">nog niet gemaakt</div>';
                    echo '<button class="button button-small" name="pd_ca" value="make_season" onclick="this.form.pd_seizoen.value=\'' . esc_attr($sz) . '\'" style="margin-top:2px" title="Genereert het seizoensbeeld nu vanuit de basis-ref (duurt ±1 minuut)">✨ nu alvast maken</button>';
                }
                if ('zomer' !== $sz) {
                    echo '<div style="margin-top:5px;border-top:1px dashed #dcdcde;padding-top:4px"><label style="font-size:10px;color:#646970;display:block">eigen beeld uploaden</label><input type="file" name="pd_szref_' . esc_attr($sz) . '" accept="image/png,image/jpeg,image/webp" style="width:100%;font-size:10px"></div>';
                }
                echo '</div>';
            }
            echo '</div><input type="hidden" name="pd_seizoen" value="">';
            echo '<p style="margin:6px 0 2px;color:#646970;font-size:12px">Niet gemaakt? Dan ontstaat de variant vanzelf zodra de plek in dat seizoen in een verhaal voorkomt (afgeleid van de basis-ref). Eigen uploads gaan mee met de knop Opslaan en winnen altijd.</p>';
        }
        echo '</div><div style="flex:1;min-width:320px">';
        $field('Naam', '<input type="text" name="pd_name" style="width:100%" value="' . esc_attr((string) ($pl['name'] ?? '')) . '">');
        $field('Beschrijving (NL)', '<textarea name="pd_beschrijving" rows="2" style="width:100%">' . esc_textarea((string) ($pl['beschrijving'] ?? '')) . '</textarea>');
        $field('Beschrijving (EN — voor de tekenaar)', '<textarea name="pd_beschrijving_en" rows="2" style="width:100%">' . esc_textarea((string) ($pl['beschrijving_en'] ?? '')) . '</textarea>');
        $field('Binnenkant (EN — hoe het er BINNEN uitziet; gebruikt bij binnen-scènes. Upload het binnenkant-beeld als ref-plekbinnen-<slug>-1.png)', '<textarea name="pd_binnen_en" rows="2" style="width:100%" placeholder="a cosy round room inside the tree: a tiny wooden bed with a moss-green blanket, a round window, warm lantern light">' . esc_textarea((string) ($pl['binnen_en'] ?? '')) . '</textarea>');
        $field('Vaste details / props (EN — bv. kleur van het deurtje, bordje, spulletjes)', '<textarea name="pd_props" rows="2" style="width:100%" placeholder="round teal-blue wooden door; small broom and potted lavender by the doorstep">' . esc_textarea((string) ($pl['props'] ?? '')) . '</textarea>');
        $field('Verboden (EN — wat hier NOOIT getekend mag worden)', '<textarea name="pd_verboden" rows="2" style="width:100%" placeholder="readable text on signs; other doors">' . esc_textarea((string) ($pl['verboden'] ?? '')) . '</textarea>');
        if ($is_new) {
            echo '<p><button class="button button-primary" name="pd_ca" value="new_place">➕ Plek toevoegen</button></p>';
        } else {
            echo '<p style="display:flex;gap:8px"><button class="button button-primary" name="pd_ca" value="save_place">Opslaan</button>'
                . '<button class="button button-link-delete" name="pd_ca" value="delete_place" onclick="return confirm(\'Deze plek verwijderen?\')">Verwijderen</button></p>';
        }
        echo '</div></div></form>';
    };
    echo '<h2 style="margin-top:24px">🏡 Plekken (' . count((array) $canon['places']) . ')</h2><div style="max-width:1100px">';
    foreach ((array) $canon['places'] as $i => $pl) {
        $slug = pd_slugify((string) ($pl['name'] ?? ''));
        $f = pd_ref_file('place', $slug);
        $thumb = '' !== $f ? '<img src="' . esc_url(PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f)) . '" style="height:44px;width:auto;border-radius:6px;vertical-align:middle;margin-right:10px" alt="">' : '';
        echo '<details style="margin:8px 0"><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px">' . $thumb . '<strong>' . esc_html((string) ($pl['name'] ?? '?')) . '</strong> <span style="color:#646970">— ' . esc_html(mb_substr((string) ($pl['beschrijving'] ?? ''), 0, 80, 'UTF-8')) . '</span></summary>'; // phpcs:ignore WordPress.Security.EscapeOutput
        $place_form((array) $pl, (int) $i);
        echo '</details>';
    }
    echo '<details style="margin:14px 0"><summary style="cursor:pointer;font-size:14px;padding:8px 12px;background:#f0f6fc;border:1px solid #dcdcde;border-radius:8px"><strong>➕ Nieuwe plek</strong></summary>';
    $place_form(null, -1);
    echo '</details></div></div>';
}

/* ====================================================================
 * [pd_vriendjes] — "De vriendjes van Mosje" op de site (v0.21.1).
 * Rendert LIVE uit de canon: nieuw personage in de canon = vanzelf op de
 * pagina, met referentiebeeld, wie het is en waar het woont. Mosje linkt
 * naar zijn eigen pagina.
 * ==================================================================== */
add_shortcode('pd_vriendjes', function () {
    if (get_current_blog_id() !== PD_BLOG) { switch_to_blog(PD_BLOG); $switched = true; } else { $switched = false; }
    $canon = pd_canon();
    $out = '<style>
      .pd-vriendjes{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:22px;margin:18px 0}
      .pd-vriend{background:#fff;border:1px solid rgba(44,40,32,.1);border-radius:16px;overflow:hidden;box-shadow:0 6px 18px rgba(44,40,32,.06)}
      .pd-vriend img{width:100%;height:240px;object-fit:cover;display:block}
      .pd-vriend__body{padding:14px 18px 18px}
      .pd-vriend__naam{margin:0 0 6px;font-size:1.25rem}
      .pd-vriend__woont{font-size:.88rem;color:#6c655a;margin:0 0 8px}
      .pd-vriend__tekst{font-size:.95rem;line-height:1.55;margin:0}
      .pd-vriend__meer{display:inline-block;margin-top:10px;font-weight:600;color:#6f7e5c}
    </style><div class="pd-vriendjes">';
    foreach ((array) ($canon['characters'] ?? array()) as $ch) {
        $naam = trim((string) ($ch['name'] ?? ''));
        if ('' === $naam) { continue; }
        $slug = pd_canon_slug($naam);
        $f = pd_ref_file('character', $slug);
        $img = '' !== $f ? '<img src="' . esc_url(PD_URL_BASE . rawurlencode(basename($f)) . '?v=' . filemtime($f)) . '" alt="' . esc_attr($naam) . '" loading="lazy">' : '';
        $is_mosje = ('mosje' === $slug);
        $out .= '<article class="pd-vriend">' . $img . '<div class="pd-vriend__body">'
            . '<h3 class="pd-vriend__naam">' . esc_html($naam) . '</h3>'
            . (!empty($ch['woont']) ? '<p class="pd-vriend__woont">🏠 Woont ' . esc_html((string) $ch['woont']) . '</p>' : '')
            . '<p class="pd-vriend__tekst">' . esc_html((string) ($ch['eigenschappen'] ?? '')) . '</p>'
            . ($is_mosje ? '<a class="pd-vriend__meer" href="' . esc_url(home_url('/wie-is-mosje/')) . '">Lees alles over Mosje &rarr;</a>' : '')
            . '</div></article>';
    }
    $out .= '</div>';
    if ($switched) { restore_current_blog(); }
    return $out;
});

/* ====================================================================
 * FEESTDAGENKALENDER (v0.22) — op deze datums krijgt de verhalenschrijver
 * automatisch het thema mee. Beweegbare feesten (Pasen, Moederdag, Vaderdag)
 * worden berekend. Wachtrij-items gaan altijd vóór (zoals bij Vaderdag 2026).
 * Eigen thema's toevoegen/overschrijven kan via optie pd_feest_extra
 * (array 'MM-DD' => 'thema-instructie').
 * ==================================================================== */
function pd_feestdag_thema(): string {
    try { $nu = new DateTime('now', new DateTimeZone('Europe/Amsterdam')); } catch (\Throwable $e) { return ''; }
    $jaar = (int) $nu->format('Y');
    $md   = $nu->format('m-d');

    // Pasen (Meeus/Gauss, geen calendar-extensie nodig).
    $a = $jaar % 19; $b = intdiv($jaar, 100); $c = $jaar % 100;
    $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30; $i = intdiv($c, 4); $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $maand = intdiv($h + $l - 7 * $m + 114, 31); $dag = (($h + $l - 7 * $m + 114) % 31) + 1;
    $pasen = sprintf('%02d-%02d', $maand, $dag);
    $moederdag = (new DateTime("second sunday of may $jaar"))->format('m-d');
    $vaderdag  = (new DateTime("third sunday of june $jaar"))->format('m-d');

    // Vaste datums eerst; berekende familiefeesten (Pasen/Moederdag/Vaderdag) daarná,
    // zodat zij winnen als ze toevallig op een zonnewende vallen (zoals Vaderdag 21-06-2026).
    $kalender = array(
        '06-21'     => 'LANGSTE DAG (zomerzonnewende): de zon blijft heel lang op; het bos viert het licht en gaat extra laat (maar kalm) slapen.',
        '10-31'     => 'HALLOWEEN, zacht: een lampionnetje-pompoentje, vriendelijke schaduwen die iets liefs blijken te zijn, een heel klein beetje spannend maar altijd veilig en geborgen. NIETS engs of griezeligs.',
        '11-11'     => 'SINT-MAARTEN: lichtjes en lampionnetjes door het bos, een liedje bij de deurtjes, iets lekkers delen.',
        '12-05'     => 'SINTERKLAAS: schoentje (of laarsje/nestje) zetten bij het deurtje, een wortel voor het paard, kleine verrassingen in de ochtend. Warm en verwachtingsvol.',
        '12-21'     => 'KORTSTE DAG (winterzonnewende): de langste nacht; het bos maakt het gezellig met lichtjes en warmte binnen, vanaf morgen komt het licht terug.',
        '12-24'     => 'KERSTAVOND: lichtjes in de boom op de open plek, samen zijn, stilletjes sneeuw of winterlicht, iedereen hoort erbij.',
        '12-25'     => 'KERST: een rustige kerstdag in het bos — samen eten, kleine cadeautjes van de natuur, dankbaar en warm.',
        '12-31'     => 'OUDJAAR: het bos kijkt zacht terug op het jaar en doet wensjes voor het nieuwe jaar. Kalm, geen vuurwerk-lawaai (hoogstens vuurvliegjes als sterretjes).',
    );
    $kalender[$pasen]     = 'PASEN: een zacht paasverhaaltje — beschilderde eitjes verstopt in het bos, lentebloemen, zoeken en samen delen. Vrolijk en licht.';
    $kalender[$moederdag] = 'MOEDERDAG: een lief verhaaltje over mama vogel of over iets liefs doen voor een mama. Warm en dankbaar.';
    $kalender[$vaderdag]  = 'VADERDAG: een lief verhaaltje over papa vogel of over iets liefs doen voor een papa. Warm en trots.';
    $extra = pd_get('pd_feest_extra');
    if (is_array($extra)) { $kalender = array_merge($kalender, $extra); }

    return isset($kalender[$md]) ? (string) $kalender[$md] : '';
}

/* ---- Seizoen + echt Nederlands weer (v0.16, gratis via Open-Meteo, 1x per dag) ---- */

/** Meteorologisch seizoen (NL-tijd): wisselt op 1 maart / 1 juni / 1 september / 1 december. */
function pd_seizoen(): string {
    try { $m = (int) (new DateTime('now', new DateTimeZone('Europe/Amsterdam')))->format('n'); } catch (\Throwable $e) { $m = (int) gmdate('n'); }
    return ($m >= 3 && $m <= 5) ? 'lente' : (($m >= 6 && $m <= 8) ? 'zomer' : (($m >= 9 && $m <= 11) ? 'herfst' : 'winter'));
}

function pd_seizoen_weer(): string {
    $cached = pd_get('pd_weer_vandaag');
    if (is_array($cached) && ($cached['dag'] ?? '') === gmdate('Y-m-d')) { return (string) $cached['tekst']; }
    $seizoen = pd_seizoen();
    $weer = '';
    // De Bilt; weathercode -> kindvriendelijke omschrijving
    $r = wp_remote_get('https://api.open-meteo.com/v1/forecast?latitude=52.11&longitude=5.18&daily=weather_code,temperature_2m_max&timezone=Europe%2FAmsterdam&forecast_days=1', array('timeout' => 10));
    if (!is_wp_error($r) && 200 === wp_remote_retrieve_response_code($r)) {
        $d = json_decode(wp_remote_retrieve_body($r), true);
        $code = (int) ($d['daily']['weather_code'][0] ?? -1);
        $temp = round((float) ($d['daily']['temperature_2m_max'][0] ?? 0));
        $map = array(0 => 'stralend zonnig', 1 => 'zonnig met een paar wolkjes', 2 => 'half bewolkt', 3 => 'bewolkt', 45 => 'mistig', 48 => 'mistig', 51 => 'een beetje miezerig', 53 => 'motregen', 55 => 'motregen', 61 => 'zachte regen', 63 => 'regenachtig', 65 => 'flinke regen', 71 => 'lichte sneeuw', 73 => 'sneeuw', 75 => 'veel sneeuw', 80 => 'af en toe een bui', 81 => 'buien', 82 => 'stevige buien', 95 => 'onweer in de verte');
        if (isset($map[$code])) { $weer = $map[$code] . ', rond ' . $temp . ' graden'; }
    }
    $tekst = 'Het is ' . $seizoen . ' in Nederland' . ('' !== $weer ? (' en het weer vandaag is: ' . $weer) : '') . '. Laat het verhaal en de beelden hier zachtjes bij passen (seizoen klopt altijd; het weer mag je sprookjesachtig vertalen, niets engs van maken). Het weer is DECOR, geen ONDERWERP: laat het meebewegen in de scènes maar maak het nooit waar het verhaal over gaat. Een aflevering over de regen is geen verhaal; een aflevering waarin iemand een papieren bootje maakt en het in een plas laat varen, wel.';
    pd_set('pd_weer_vandaag', array('dag' => gmdate('Y-m-d'), 'tekst' => $tekst));
    return $tekst;
}

/* ---- Doorlopende dagdelen-cyclus (v0.16.1): elke aflevering schuift één stap op,
 * alsof het bos nooit ophoudt. Seizoen/weer blijven echt (pd_seizoen_weer). ---- */
function pd_dagdeel_stap(): array {
    $cyc = array('vroege ochtend', 'ochtend', 'middag', 'namiddag', 'schemering', 'avond', 'late avond', 'nacht');
    $n = count($cyc);
    $i = (int) pd_get('pd_dagdeel_idx', 5); // start bij 'avond' (bedtijdkanaal)
    $cur = $cyc[$i % $n];
    $prev = $cyc[($i - 1 + $n) % $n];
    $next = $cyc[($i + 1) % $n];
    pd_set('pd_dagdeel_idx', ($i + 1) % $n);
    return array($cur, $next, $prev);
}

/* ====================================================================
 * EERLIJKE ROL- EN PLEKROTATIE + ZACHT LESJE (v0.23)
 * De schrijver koos vroeger zelf wie meedeed en waar — en neigde steeds
 * naar Mosje + Kwakkel op dezelfde paar plekken. Hier kiezen we VOORAF,
 * least-recently-used, zodat élk vriendje en élke plek echt aan de beurt
 * komt. Mosje is er altijd bij (vaste gezicht) en heeft echt interactie;
 * de co-ster(ren), de hoofdrol én de plek rouleren. Soms een groepsdag.
 * Elk verhaal krijgt bovendien één klein, zacht levenslesje mee.
 * ==================================================================== */

/* Sorteert $items van 'langst niet gebruikt' naar 'recentst'. $recent staat
 * recent-eerst (index 0 = laatst gebruikt). Items die niet in $recent staan
 * komen vooraan; bij gelijke stand telt de oorspronkelijke volgorde (stabiel). */
function pd_lru_order(array $items, array $recent): array {
    $rank = array(); foreach ($recent as $i => $s) { if (!isset($rank[$s])) { $rank[$s] = $i; } }
    $big = count($recent) + 1;
    $orig = array(); foreach (array_values($items) as $i => $s) { if (!isset($orig[$s])) { $orig[$s] = $i; } }
    usort($items, function ($a, $b) use ($rank, $big, $orig) {
        $ra = isset($rank[$a]) ? $rank[$a] : $big;
        $rb = isset($rank[$b]) ? $rank[$b] : $big;
        if ($ra !== $rb) { return $rb <=> $ra; }
        return ($orig[$a] ?? 0) <=> ($orig[$b] ?? 0);
    });
    return $items;
}
function pd_dedup_keep_first(array $list): array {
    $seen = array(); $out = array();
    foreach ($list as $v) { if (!isset($seen[$v])) { $seen[$v] = true; $out[] = $v; } }
    return $out;
}

/* Bepaalt VOORAF wie er vandaag meedoen (naast Mosje), wie de hoofdrol heeft
 * en waar het speelt. Werkt de gebruik-logs bij (zodat alles blijft rouleren)
 * en geeft een mandaat-tekst voor de schrijver terug. */
function pd_cast_plan(int $epnum, string $dagdeel): array {
    $canon = pd_canon();

    // 's avonds mag de zingende nachtbloem meedoen; overdag laten we haar rusten.
    $avond = (false !== mb_stripos($dagdeel, 'avond')) || (false !== mb_stripos($dagdeel, 'nacht')) || (false !== mb_stripos($dagdeel, 'schemering'));

    $cast = array(); // slug => naam, alle co-sterren (Mosje uitgezonderd)
    foreach ((array) ($canon['characters'] ?? array()) as $ch) {
        $name = trim((string) ($ch['name'] ?? ''));
        if ('' === $name) { continue; }
        $slug = pd_canon_slug($name);
        if ('mosje' === $slug) { continue; }
        if ('nachtbloem' === $slug && !$avond) { continue; }
        $cast[$slug] = $name;
    }
    if (!$cast) { return array('mandate' => ''); }

    // Groepsgrootte varieert over een cyclus van 8 afleveringen: meestal 1 vriendje,
    // soms 2, af en toe een grote samen-dag waarop Mosje iedereen bij elkaar brengt.
    $cyc = $epnum % 8;
    if (0 === $cyc) { $group = 4; } elseif (4 === $cyc) { $group = 3; } elseif (2 === $cyc || 6 === $cyc) { $group = 2; } else { $group = 1; }

    // Least-recently-used keuze van de co-sterren.
    $clog  = array_values((array) pd_get('pd_spotlight_log', array()));
    $stale = pd_lru_order(array_keys($cast), $clog);
    $group = max(1, min($group, count($stale)));
    $chosen = array_slice($stale, 0, $group);

    // Canon-koppel: Bloempje praat niet en hoort bij Roosje — nooit Bloempje zonder Roosje.
    if (in_array('bloempje', $chosen, true) && !in_array('roosje', $chosen, true) && isset($cast['roosje'])) {
        $chosen[] = 'roosje';
    }

    // Hoofdrol: de meest achtergebleven co-ster draagt het verhaal. De nachtbloem
    // (een vaste bloem, geen drager) leidt nooit; op een grote samen-dag is Mosje
    // de gastheer.
    $hoofd_naam = 'Mosje';
    if ($group < 3) {
        foreach ($chosen as $s) { if ('nachtbloem' !== $s) { $hoofd_naam = $cast[$s]; break; } }
    }

    // Plek-rotatie (LRU over alle plekken).
    $places = array();
    foreach ((array) ($canon['places'] ?? array()) as $pl) {
        $pn = trim((string) ($pl['name'] ?? ''));
        if ('' !== $pn) { $places[pd_slugify($pn)] = $pn; }
    }
    $plog = array_values((array) pd_get('pd_place_log', array()));
    $place_name = ''; $place_slug = '';
    if ($places) {
        $pstale = pd_lru_order(array_keys($places), $plog);
        $place_slug = $pstale[0];
        $place_name = $places[$place_slug];
    }

    // Logs bijwerken zodat de rotatie de volgende keer verder draait.
    foreach (array_reverse($chosen) as $s) { array_unshift($clog, $s); }
    pd_set('pd_spotlight_log', array_slice(pd_dedup_keep_first($clog), 0, max(8, count($cast))));
    if ('' !== $place_slug) {
        array_unshift($plog, $place_slug);
        pd_set('pd_place_log', array_slice(pd_dedup_keep_first($plog), 0, max(8, count($places))));
    }

    // Mandaat-tekst voor de schrijver.
    $namen = array(); foreach ($chosen as $s) { $namen[] = $cast[$s]; }
    if (count($namen) === 1) {
        $wie = "Vandaag beleeft Mosje samen met {$namen[0]} een klein avontuurtje.";
    } else {
        $laatste = $namen[count($namen) - 1];
        $kop = array_slice($namen, 0, -1);
        $wie = "Vandaag zijn Mosje en zijn vriendjes samen op pad: " . implode(', ', $kop) . " en {$laatste}.";
    }
    $waar  = '' !== $place_name ? " Het verhaal speelt zich vooral af bij {$place_name}; gebruik die plek echt en herkenbaar, niet zomaar een algemeen groen bos." : '';
    $hoofd = " Geef {$hoofd_naam} vandaag de hoofdrol: {$hoofd_naam} draagt de vraag of het kleine probleempje van het verhaal en staat groot op de thumbnail. Mosje is er altijd bij en heeft echt contact met de anderen, maar hoeft niet de hoofdpersoon te zijn.";
    $mandate = "ROLVERDELING VANDAAG (houd je hieraan, gebruik exact deze namen):\n" . $wie . $waar . $hoofd;

    return array('mandate' => $mandate, 'hoofd' => $hoofd_naam, 'place' => $place_name, 'spotlight' => $chosen, 'cast_namen' => $namen);
}

/* Kleine, zachte levenslesjes — rouleren least-recently-used, zodat ze niet
 * steeds hetzelfde zijn. Eigen lesjes bijzetten via optie pd_lessen_extra (array). */
function pd_lessen_lijst(): array {
    $base = array(
        'Vriendelijk zijn: iemand staat alleen of wil meedoen. Een vriendje maakt zichtbaar plaats en nodigt diegene uit. Daarna kunnen ze samen verder.',
        'Helpen: iemand krijgt een concrete klus niet alleen voor elkaar. Een vriendje pakt één kant of doet één duidelijke stap mee. Daardoor lukt de klus wel.',
        'Luisteren: iemand vertelt of laat zien wat er nodig is. Het andere vriendje stopt even, kijkt naar diegene en doet daarna precies wat gevraagd werd. Daardoor gaat het niet mis.',
        'Delen: er is één ding voor meerdere vriendjes. De hoofdpersoon verdeelt het zichtbaar in stukjes of geeft het door. Daarna heeft iedereen iets.',
        'Sorry zeggen: een personage doet per ongeluk iets vervelends. Diegene zegt kort sorry en herstelt de concrete schade. Daarna kunnen ze weer samen verder.',
        'Dankjewel zeggen: iemand krijgt hulp of een klein cadeautje. Diegene zegt duidelijk dankjewel en laat zien wat de hulp mogelijk maakte.',
        'Geduld: iets is nog niet klaar en te vroeg handelen zou het verstoren. De vriendjes wachten samen en kijken nog eens. Daarna is het wel klaar.',
        'Opruimen: er ligt rommel die een dier of vriendje hindert. Iedereen pakt iets op en brengt het naar de goede plek. Daarna is de plek weer veilig en bruikbaar.',
        'Een vraag stellen: een personage weet concreet niet wat iets is of hoe iets moet. Diegene vraagt het hardop. Een vriendje laat het antwoord zien en daarna kan de hoofdpersoon het zelf.',
        'Voor een plant zorgen: een plantje mist zichtbaar water, licht of steun. De vriendjes geven precies wat nodig is. Later staat het plantje merkbaar steviger of frisser.',
        'Een plant leren kennen: de vriendjes vinden één herkenbare plant. Ze benoemen de naam en één eenvoudig kenmerk dat ze kunnen zien, ruiken of voelen. Daarna herkennen ze dezelfde plant opnieuw.',
        'Een bosdiertje leren kennen: de vriendjes zien één klein dier. Ze benoemen de naam en één eenvoudige manier waarop het dier leeft of beweegt. Daarna geven ze het dier rustig de ruimte.',
        'Samen veilig in het donker: iets is moeilijk te zien. De vriendjes blijven bij elkaar en gebruiken een klein lichtje of elkaars hand. Daardoor vinden ze rustig hun weg.',
        'Iedereen is anders: twee personages kunnen niet hetzelfde, maar ieder kan één andere concrete taak goed. Door die twee talenten te combineren lukt het samen.',
        'Eerst zelf proberen: iets kleins lukt niet meteen. De hoofdpersoon probeert een tweede eenvoudige manier voordat hulp wordt gevraagd. Daardoor lukt één stap zelf.',
        'Voorzichtig zijn: iets kleins of breekbaars kan beschadigen. De hoofdpersoon gebruikt twee handen, loopt langzaam of legt het zacht neer. Daardoor blijft het heel en veilig.',
    );
    foreach ((array) pd_get('pd_lessen_extra', array()) as $e) { $e = trim((string) $e); if ('' !== $e) { $base[] = $e; } }
    return array_values(array_unique($base));
}
function pd_lesson_pick(): string {
    $lessen = pd_lessen_lijst();
    if (!$lessen) { return ''; }
    $log = array_values((array) pd_get('pd_lesson_log', array()));
    $les = pd_lru_order($lessen, $log)[0];
    array_unshift($log, $les);
    pd_set('pd_lesson_log', array_slice(pd_dedup_keep_first($log), 0, max(8, (int) floor(count($lessen) * 0.7))));
    return $les;
}

/* ====================================================================
 * DAG-AVONTUREN IN DELEN (v0.24)
 * Eén dag wordt over 3 afleveringen verteld (ochtend/middag/avond), elk
 * bordurend op het vorige deel, zodat de dag lang en rijk voelt zonder in
 * één keer een lang verhaal te schrijven. De rol-/plek-/lesrotatie werkt nu
 * op DAG-niveau: bij een nieuwe dag wordt de kerncast + hoofdrol + lesje
 * gekozen en die blijven de hele dag consistent. Elk deel blijft op zichzelf
 * te begrijpen (zachte terugblik-zin) en eindigt kalm; de avond sluit de dag
 * slaperig af. Uit te zetten via optie pd_arcs=0 (terug naar per-aflevering).
 * ==================================================================== */
function pd_arc_get(): array {
    $raw = pd_get('pd_arc', '');
    $arc = is_array($raw) ? $raw : json_decode((string) $raw, true);
    return is_array($arc) ? $arc : array();
}
function pd_arc_save(array $arc): void { pd_set('pd_arc', wp_json_encode($arc, JSON_UNESCAPED_UNICODE)); }

/* Zet het deel van vandaag klaar: nieuwe dag (kies cast+lesje) of volgend deel
 * van de lopende dag. Wordt VÓÓR het schrijven aangeroepen. */
function pd_arc_plan(int $epnum): array {
    $arc = pd_arc_get();
    $dayparts = array('ochtend', 'middag', 'avond');
    $new_day = empty($arc) || ((int) ($arc['part'] ?? 0) >= 3);
    if ($new_day) {
        $plan = pd_cast_plan($epnum, 'middag'); // 'middag' => nachtbloem niet in de kern-cast (mag 's avonds wel als sfeer)
        $arc = array(
            'day_id'     => (int) ($arc['day_id'] ?? 0) + 1,
            'part'       => 1,
            'lead'       => (string) ($plan['hoofd'] ?? 'Mosje'),
            'cast_namen' => (array) ($plan['cast_namen'] ?? array()),
            'place'      => (string) ($plan['place'] ?? ''),
            'lesson'     => (string) pd_lesson_pick(),
            'mandate'    => (string) ($plan['mandate'] ?? ''),
            'title_base' => '',
            'so_far'     => '',
        );
    } else {
        $arc['part'] = (int) $arc['part'] + 1;
    }
    pd_arc_save($arc);
    $arc['daypart'] = $dayparts[max(0, min(2, (int) $arc['part'] - 1))];
    $arc['is_new']  = $new_day;
    return $arc;
}

/* Onthoud na het schrijven de titel-basis (deel 1) en wat er tot nu toe
 * gebeurde, zodat het volgende deel erop kan voortborduren. */
function pd_arc_record(string $title_base, string $summary): void {
    $arc = pd_arc_get();
    if (empty($arc)) { return; }
    if (1 === (int) ($arc['part'] ?? 0)) {
        if ('' === (string) ($arc['title_base'] ?? '')) { $arc['title_base'] = $title_base; }
        $arc['so_far'] = $summary;
    } else {
        $arc['so_far'] = trim((string) ($arc['so_far'] ?? '') . ' ' . $summary);
    }
    pd_arc_save($arc);
}

/* ---- 1) gpt-4o verhaal (leest de bijbel, breidt 'm uit) ---- */
function pd_generate_story(string $key, array $log) {
    // Vooraf geschreven scripts (wachtrij) gaan vóór op auto-generatie. FIFO.
    // Items met een "date" (YYYY-MM-DD) spelen ALLEEN op die datum (seizoensafleveringen vooruit plannen).
    $q = pd_get('pd_script_queue', '');
    $queue = is_array($q) ? $q : json_decode((string) $q, true);
    if (is_array($queue) && !empty($queue)) {
        try { $vandaag = (new DateTime('now', new DateTimeZone('Europe/Amsterdam')))->format('Y-m-d'); } catch (\Throwable $e) { $vandaag = gmdate('Y-m-d'); }
        $story = null;
        foreach ($queue as $i => $item) {
            $d = is_array($item) ? trim((string) ($item['date'] ?? '')) : '';
            if ('' === $d || $d === $vandaag) { $story = $item; break; }
        }
        if (null === $story) { // alle items zijn datum-gebonden voor een andere dag
            pd_log('Wachtrij bevat alleen datum-gebonden items voor later — auto-generatie.');
        } else {
        // BEWUST nog NIET uit de wachtrij halen: dat gebeurt pas als fase A
        // slaagt (pd_queue_remove in pd_run_daily). Sterft de run halverwege
        // (timeout/abort), dan blijft het verhaal bewaard — 2026-06-05 ging
        // "Belle en de bal van Happy" zo twee keer ongepubliceerd verloren.
        $story['_queue_title'] = trim((string) ($story['title'] ?? ''));
        $scenes = is_array($story['scenes'] ?? null) ? array_values((array) $story['scenes']) : array();
        if (is_array($story) && count($scenes) >= 5 && count($scenes) <= 7) {
        $story['scenes'] = $scenes;
            $story['title']     = trim((string) ($story['title'] ?? 'Een verhaaltje uit het Praatdeurtjesbos'));
            $story['character'] = (string) ($story['character'] ?? 'Mosje');
            $story['new_characters'] = is_array($story['new_characters'] ?? null) ? $story['new_characters'] : array();
            $story['new_places']     = is_array($story['new_places'] ?? null) ? $story['new_places'] : array();
            pd_log('Verhaal uit wachtrij gebruikt: "' . $story['title'] . '".');
            return pd_clean_story($story);
        }
        pd_log('Wachtrij-item ongeldig (verwacht 5–7 scènes) — terug naar auto-generatie.');
        }
    }

    $target = (int) (pd_get('pd_target_words') ?: PD_TARGET_WORDS);
    // v0.30: arc-mode = "een moment" → korter verhaal (minder ruimte voor mini-arc)
    if ('0' !== (string) pd_get('pd_arcs', '1')) {
        $arc_target = (int) (pd_get('pd_arc_target_words') ?: 320);
        if ($arc_target > 0) { $target = $arc_target; }
    }
    $epnum = 1; foreach ($log as $e) { $epnum = max($epnum, (int) ($e['ep'] ?? 0) + 1); }
    $recent = array();
    foreach (array_slice($log, 0, 12) as $e) { $recent[] = '#' . ($e['ep'] ?? '?') . ' ' . ($e['title'] ?? '') . ' — ' . ($e['summary'] ?? ''); }
    $recent_txt = $recent ? implode("\n", $recent) : '(nog geen eerdere afleveringen)';

    $system = "Je schrijft een dagelijks slaapverhaaltje voor het kanaal 'Praatdeurtje slaapverhaaltjes'.\n\n"
        . "=== DE VASTE WERELD (blijf hier altijd consistent mee) ===\n" . pd_canon_text() . "\n"
        . "DOELGROEP: peuters en kleuters (2 t/m 6 jaar).\n"
        . "TAALREGELS (heel belangrijk):\n"
        . "- NIJNTJE-STIJL: elke zin maximaal 8 woorden, liever 5 of 6. Geen bijzinnen (geen 'omdat', 'terwijl', 'zodat', 'waarna', 'die/dat'-constructies). Eén gedachte per zin. Denk aan Dick Bruna.\n"
        . "- Alleen alledaagse, eenvoudige woorden. Vermijd moeilijke/abstracte/formele woorden zoals 'melodie', 'betoverend', 'fascinerend', 'glooiend', 'geroezemoes', 'tafereel'. Gebruik gewone woorden: 'liedje', 'mooi', 'zacht', 'fijn', 'blij'.\n"
        . "- Vermijd 'wiebelt/wiebelen/gewiebel' (uitzondering: Bloempje het konijntje wiebelt zijn neusje — dat is zijn vaste trekje en mag).\n"
        . "- Veel zachte herhaling. Rustige, knusse toon. Geen spanning, niets engs of verdrietigs. Het einde is altijd kalm: iedereen gaat tevreden slapen.\n"
        . "- Hooguit één nieuw woordje per verhaal, en leg dat meteen in dezelfde zin uit.\n"
        . "- Verwerk in elk verhaaltje één klein levenslesje (welk lesje krijg je in de opdracht hieronder). Maak het begrijpelijk door oorzaak, handeling en zichtbaar gevolg te tonen. Nooit een preek of losse moraal aan het eind.\n"
        . "- Gebruik NOOIT gedachtestreepjes (— of –) of een los streepje tussen woorden. Schrijf gewoon met komma's en punten. Dat klinkt menselijker.\n"
        . "SCHRIJFSTIJL: Nijntje-stijl. Precies zoals Dick Bruna schrijft — heel eenvoudig, heel direct.\n"
        . "- Elke zin: maximaal 8 woorden. Liever 5 of 6. Varieer vrij in aantal zinnen per scène.\n"
        . "- Geen bijzinnen (geen 'omdat', 'terwijl', 'zodat', 'waarna', 'die/dat'-zinnen).\n"
        . "- Geen beschrijvende uitweidingen. Schrijf wat er gebeurt, niet hoe het voelt of eruit ziet.\n"
        . "- Totaal het hele verhaal: ongeveer {$target} woorden (plus of min 20). Dat geeft de juiste speelduur.\n"
        . "Voorbeeld van de juiste toon: 'Mosje ziet een bolletje wol. Het rolt weg. Hij rent erachteraan. Maar het bolletje rolt steeds verder. Mosje hijgt en lacht tegelijk.'\n"
        . "Elke aflevering staat op zichzelf.\n"
        . "Je mag bestaande personages en plekken uit de wereld gebruiken (consistent!), en je MAG een nieuw personage of een nieuwe plek introduceren. Doe je dat, beschrijf het dan kort in new_characters / new_places zodat we het onthouden voor later.\n\n"
        . "VERHAAL-SKELET (verplicht, anders is het geen verhaal maar een sfeerstuk):\n"
        . "- Elke aflevering heeft één kleine, CONCRETE wens of vraag van de hoofdpersoon — iets fysieks dat je kunt zien of doen (een papieren bootje maken, een gladde steen zoeken voor een vriendje, een paddenstoeltje verplaatsen, een liedje aan iemand leren, een verdwaalde knoop terugbrengen). NOOIT abstract ('iets begrijpen', 'een gevoel voelen', 'genieten van de regen').\n"
        . "- Kies één hoofdwerkwoord uit deze lijst en laat dat in MINSTENS 4 van de 6 scènes echt fysiek gebeuren: zoeken, maken, geven, brengen, repareren, planten, bouwen, verstoppen, vinden, delen, leren (iets aan iemand), helpen (iets concreets), oversteken, vangen (een blad, een veertje). NIET als hoofdwerkwoord: voelen, horen, kijken, fluisteren, dromen, wensen, denken — die mogen wel in zinnen voorkomen, maar dragen het verhaal niet.\n"
        . "- De wens loopt door alle 6 scènes en wordt in de laatste scène vervuld of opgelost. Geen wens, geen verhaal.\n"
        . "- Het einde blijft kalm en slaperig (iedereen gaat tevreden naar bed) — dat blokkeert spanning, niet plot.\n\n"
        . "Geef ALLEEN JSON terug met exact deze velden: "
        . "{\"title\": string (NL), \"character\": string (hoofdpersoon, meestal \"Mosje\"), \"summary\": string (1 NL zin voor het archief), "
        . "\"wens\": string (1 NL zin: wat wil/zoekt/maakt/brengt de hoofdpersoon vandaag? Concreet, fysiek, met een ding of plek erin), "
        . "\"hoofdwerkwoord\": string (één werkwoord uit de toegestane lijst), "
        . "\"oplossing\": string (1 NL zin: hoe komt de wens er aan het eind van de laatste scène uit? Klein en concreet), "
        . "\"elements\": string[], "
        . "\"new_characters\": [{\"name\": string, \"uiterlijk\": string (NL), \"uiterlijk_en\": string (KORTE Engelse visuele beschrijving voor de illustrator), \"woont\": string (NL), \"eigenschappen\": string (NL)}] (leeg [] als geen nieuw personage), "
        . "\"new_places\": [{\"name\": string, \"beschrijving\": string (NL), \"beschrijving_en\": string (KORTE Engelse visuele beschrijving voor de illustrator)}] (leeg [] als geen nieuwe plek), "
        . "\"continuity\": string (KORTE Engelse lijst met vaste visuele details die in ALLE scènes identiek moeten blijven: kleur van blaadjes/bloemen, seizoen, weer, tijd van de dag, en kleur+aantal van voorwerpen die in DIT verhaal voorkomen; bijv. \"fresh green spring leaves; soft late-afternoon light\". Noem UITSLUITEND dingen die echt in dit verhaal zitten; noem NOOIT spulletjes van personages die niet meespelen), "
        . "\"thumbnail\": {\"main_focus\": string (EN, HET unieke onderwerp van deze aflevering, groot in beeld), \"composition_type\": string (kies uit: magic_object_focus, location_focus, emotion_closeup, action_scene, mystery_door, cozy_group_moment, overhead_map_like, tiny_character_big_world), \"characters\": string[] (max 2, exact gespeld; de HOOFDPERSOON van deze aflevering, het personage dat de vraag of het probleem draagt, staat hier altijd in; Mosje alleen toevoegen als hij echt een rol heeft, en klein als hij niet de drager is), \"location\": string (uit de plekkenlijst of leeg), \"mood\": string (EN), \"camera_angle\": string (EN), \"must_include\": string (EN), \"must_avoid\": string (EN)}, "
        . "\"scenes\": [{\"text\": string (NL verhaaltekst), \"image\": string (KORTE Engelse beschrijving van de illustratie, passend bij de tekst, zonder tekst in beeld), "
        . "\"characters\": string[] (de namen van de personages die in deze scène IN BEELD zijn, EXACT gespeld zoals in de personagelijst hierboven; nieuw personage = de naam uit new_characters), "
        . "\"places\": string[] (de plek(ken) uit de plekkenlijst die in deze scène in beeld zijn, exact gespeld; leeg [] als geen vaste plek), "
        . "\"indoor_place\": string (ALLEEN als deze scène zich BINNEN in een woonplek afspeelt: de naam van die plek, exact gespeld; anders weglaten of leeg laten), "
        . "\"visual_direction\": {\"composition_type\": string (kies uit: wide_location, closeup_object, character_closeup, over_the_shoulder, low_grass_view, top_down_map_like, doorway_view, action_diagonal, cozy_circle, hidden_peek, foreground_background_depth, tiny_character_big_world), \"main_visual_focus\": string (EN), \"camera_angle\": string (EN), \"shot_size\": string (EN), \"character_placement\": string (EN, waar staan de personages in het kader)}, "
        . "\"character_poses\": [{\"name\": string (exact gespeld), \"view_angle\": string (EN, uit de toegestane poses/aanzichten van dat personage), \"pose\": string (EN), \"gesture\": string (EN), \"expression\": string (EN)}] (één per personage in characters)}] (precies 6)}.\n\n"
        . "BEELDREGIE-REGELS (heel belangrijk voor variatie):\n"
        . "- Gebruik in één aflevering NOOIT twee keer hetzelfde visual_direction composition_type.\n"
        . "- Mosje staat NIET standaard links en een vriendje NIET standaard rechts; niet elke scène twee personages stil tegenover elkaar.\n"
        . "- Minstens één scène heeft een object of plek als hoofdonderwerp (personages klein of afwezig).\n"
        . "- Minstens één scène gebruikt een bijzondere camerahoek (low_grass_view, over_the_shoulder of top_down_map_like).\n"
        . "- Gebruik de specifieke plek van de scène, nooit een generiek groen bos.\n"
        . "- HOOFDPERSOON: het \"character\"-veld is het personage dat de vraag of het probleem van dit verhaal draagt. Dat personage is het langst in beeld, krijgt de emotionele momenten, en staat prominent op de thumbnail. Mosje is het vaste gezicht van het kanaal maar hoeft NIET elke aflevering de hoofdpersoon te zijn.\n"
        . "- POSES: geef elk personage per scène een eigen view_angle/pose/gesture/expression uit zijn toegestane lijst. Mosje neemt per aflevering minstens TWEE verschillende houdingen aan en wisselt van aanzicht (vooraanzicht, profiel links/rechts, driekwart). Laat personages natuurlijk bewegen: hurken, lopen, zitten, springen — niet vijf scènes stilstaan.\n"
        . "- BINNEN/BUITEN: speelt een scène zich binnen af (in bed, aan tafel, schuilen voor de regen), zet dan indoor_place op die plek. Beschrijf in \"image\" dan het INTERIEUR (vanaf binnen gezien); nooit het huisje van buiten met een weggelaten muur.\n"
        . "- De thumbnail is een POSTER van de aflevering, geen gewone scène: het unieke onderwerp van de titel moet zichtbaar zijn zonder de titel te lezen.";

    // Anti-herhaling: recente visuele patronen + thumbnail-types meegeven zodat de
    // planner bewust ándere composities kiest (zelfde wereld, ander beeldidee).
    $sigs = (array) pd_get('pd_visual_log', array());
    $sig_txt = $sigs ? ("\nVermijd deze recente visuele patronen in de beeldregie:\n- " . implode("\n- ", array_slice($sigs, 0, 10))) : '';
    $ttypes = (array) pd_get('pd_thumb_types', array());
    $thumb_txt = $ttypes ? ("\nDe vorige thumbnails gebruikten composition_type: " . implode(', ', array_slice($ttypes, 0, 2)) . " — kies voor de thumbnail een ÁNDER type.") : '';

    $feest = pd_feestdag_thema(); // v0.22: feestdagenkalender — vandaag een feest? dan is dát het thema
    $feest_txt = '' !== $feest ? "\nTHEMA VAN VANDAAG (verwerk dit als kern van het verhaal): {$feest}" : '';

    $arcs_on = ('0' !== (string) pd_get('pd_arcs', '1')); // v0.24: dag-avonturen in 3 delen
    $part = 1; $arc = array(); $dd_cur = 'avond';
    if ($arcs_on) {
        $arc  = pd_arc_plan($epnum);
        $part = (int) $arc['part'];
        $dd_cur = (string) $arc['daypart'];
        $les  = (string) $arc['lesson'];
        $cast_txt = '' !== ($arc['mandate'] ?? '') ? ("\n" . $arc['mandate']) : '';
        // v0.30: elk deel = ÉÉN moment in een dag, geen mini-arc met doel/plan/oplossing.
        // Het verhaaltje begint en eindigt midden in een rustig moment; geen klassiek
        // begin/midden/eind. De grotere dag-boog leeft alleen in de drie titels samen
        // en in het "Dit avontuur in delen"-blokje, niet in de spanningsboog binnen één deel.
        if (1 === $part) {
            $deel_txt = "Dit verhaaltje speelt zich af op ÉÉN OCHTEND in het bos (deel 1 van 3 — alleen ter info, niet noemen in het verhaal). Schrijf één klein moment uit die ochtend: wat doen de vriendjes nu, hier, in het zachte ochtendlicht? Het is GEEN compleet verhaal met begin, midden en eind. Geen groot probleem, geen doel voor de hele dag en geen uitgesproken moraal. Wel mag één kleine handeling een direct, begrijpelijk gevolg hebben. Het mag halverwege beginnen, alsof we even komen kijken. Het eindigt ook gewoon, zonder grote afsluiting: de ochtend gaat door, ze blijven nog even verder doen wat ze deden.";
        } elseif (2 === $part) {
            $deel_txt = "Dit verhaaltje speelt zich af op ÉÉN MIDDAG in het bos (deel 2 van 3 — alleen ter info, niet noemen in het verhaal). Schrijf één klein middag-moment: zelfde vriendjes, zelfde plek, zelfde sfeer als gisteren (cast en plek krijg je hieronder). Maar GEEN voortzetting van een 'plan' uit de ochtend — geen terugblik in het verhaal zelf (de kijker zag de ochtend gister/eergister en de chronologische volgorde leeft buiten het verhaal: in de YouTube-playlist en in het 'Dit avontuur in delen'-blok onder de post). Schrijf gewoon één rustig middag-moment dat op zichzelf staat: een kort klusje, een zacht spelletje, even zitten in de schaduw, iemand voorbij zien komen. GEEN compleet verhaal met probleem en oplossing.";
        } else {
            $deel_txt = "Dit verhaaltje speelt zich af op ÉÉN AVOND in het bos (deel 3 van 3 — alleen ter info, niet noemen in het verhaal). Schrijf één klein avond-moment: het wordt schemerig, de geluiden veranderen, sterren komen op. Geen terugblik in het verhaal zelf op ochtend of middag (die chronologie leeft buiten het verhaal). Geen 'plan dat eindelijk afgerond wordt' — gewoon één rustig moment in de avond (samen kijken naar de lucht, één laatste klein dingetje, een zachte ontmoeting). De zingende nachtbloem mag voorzichtig gloeien als het past. Het eindigt slaperig en zacht — de dag is gewoon op, de vriendjes drijven richting slaap.";
        }
        $dagdeel_txt = "MOMENT IN DE DAG (deel {$part} van 3): {$deel_txt}\n"
            . "KERNREGEL: dit verhaaltje is een MOMENT, geen DAG. Geen begin-midden-eind in dit deel. Geen doel of plan dat in dit deel afgerond wordt. Eén rustige scène uit het bos die voorbijgaat. Houd dezelfde hoofdrol en vriendjes aan als in de rolverdeling hieronder; één plek (geen reis door het bos). Het seizoen en het weer hierboven kloppen altijd.\n"
            . "OVERRIDE OP HET VERHAAL-SKELET (heel belangrijk — leest voor op de regels hierboven over wens/oplossing/hoofdwerkwoord):\n"
            . "- 'wens' is hier GEEN groot doel voor de dag, maar één klein concreet dingetje dat ze nu in dit moment aan het doen zijn (een blaadje wegvegen, een veertje oprapen, een kruimel delen, naar iets kijken samen).\n"
            . "- 'oplossing' is geen climax. Vul 'oplossing' in als één zin die laat zien dat het moment voorbij is, niet dat er iets opgelost is (bijv. 'ze blijven nog even zitten en luisteren naar het bos', of 'de wind draait en ze gaan rustig verder').\n"
            . "- Het hoofdwerkwoord mag, maar de regel '4 van de 6 scènes' is hier LOSGELATEN: laat het werkwoord rustig voorkomen, niet als motor van het verhaal. Sfeer en kleine handelingen mogen hier de scènes dragen.\n"
            . "- Schrijf rustig en kort. Liever 6 zachte scènes die niet 'ergens naartoe gaan' dan een mini-arc met opbouw en afronding.\n"
            . "- Eindig in scène 6 NIET met 'iedereen gaat tevreden naar bed' tenzij dit deel 3 (de avond) is. In deel 1 en 2 loopt het moment gewoon zachtjes uit — de dag gaat door buiten beeld.";
    } else {
        list($dd_cur, $dd_next, $dd_prev) = pd_dagdeel_stap();
        $dagdeel_txt = "TIJD VAN DE DAG: het vorige verhaal speelde in de {$dd_prev}. Dit verhaal begint in de {$dd_cur} en mag in de loop van het verhaal zachtjes richting de {$dd_next} bewegen, alsof het bos gewoon doorleeft en elk verhaal het vorige opvolgt. Benoem het dagdeel en het licht in de scènes en in continuity. Het seizoen en het weer hierboven kloppen altijd. Het einde blijft kalm en slaperig, wat het dagdeel ook is.";
        $cast_plan = pd_cast_plan($epnum, $dd_cur); // v0.23: eerlijke rol-/plekrotatie (LRU)
        $cast_txt = '' !== ($cast_plan['mandate'] ?? '') ? ("\n" . $cast_plan['mandate']) : '';
        $les = pd_lesson_pick(); // v0.23: zacht lesje, rouleert
    }
    $les_txt = '' !== $les ? ("\nKLEIN LESJE VANDAAG: {$les}\n"
        . "MAAK DIT LESJE ZICHTBAAR EN BEGRIJPELIJK VOOR EEN KIND VAN 3 JAAR:\n"
        . "1. Toon eerst concreet waarom de handeling nodig is.\n"
        . "2. Laat een personage de bedoelde handeling echt uitvoeren, niet alleen voelen, kijken of erover denken.\n"
        . "3. Toon direct wat er daardoor beter, makkelijker of fijner wordt.\n"
        . "4. Laat precies één personage de kern benoemen in een natuurlijke zin van maximaal 8 woorden, bijvoorbeeld 'Jij mag ook een stukje' of 'Ik luister naar wat jij zegt'.\n"
        . "Het lesje mag niet alleen in de titel, samenvatting, sfeer of laatste zin staan. Een kind moet na afloop kunnen zeggen: dit deed het vriendje, en daardoor gebeurde dat. Gebruik nooit de woorden 'de les', 'geleerd', 'belangrijk' of 'moraal'.") : '';
    if ($arcs_on && $part > 1) {
        $closing = "\nSchrijf dit deel verder in dezelfde stijl en sfeer; blijf trouw aan wat er eerder vandaag gebeurde en aan dezelfde vriendjes en plek.";
    } else {
        $closing = "\nVerzin een nieuw, rustig klein avontuurtje in het Praatdeurtjesbos.\nDeze dagen bestaan al, kies een ánder onderwerp (geen herhaling):\n{$recent_txt}";
    }
    $user = "Schrijf aflevering {$epnum}.\n" . pd_seizoen_weer() . "\n{$dagdeel_txt}{$feest_txt}{$cast_txt}{$les_txt}{$closing}{$sig_txt}{$thumb_txt}";

    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 120,
        'headers' => array('Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array(
            'model' => 'gpt-5.4-mini', 'temperature' => 0.85, 'max_completion_tokens' => 4000,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(array('role' => 'system', 'content' => $system), array('role' => 'user', 'content' => $user)),
        ), JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($resp)) { return $resp; }
    if (200 !== wp_remote_retrieve_response_code($resp)) { return new WP_Error('pd_story_http', 'OpenAI HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    pd_cost_add('story_tokens', (int) ($data['usage']['total_tokens'] ?? 0));
    pd_cost_add('episodes');
    $content = $data['choices'][0]['message']['content'] ?? '';
    $story = json_decode((string) $content, true);
    if (!is_array($story) || empty($story['scenes']) || count($story['scenes']) < 5 || count($story['scenes']) > 7) { return new WP_Error('pd_story_shape', 'Verhaal heeft geen 5–7 scènes.'); }
    $story['title'] = trim((string) ($story['title'] ?? 'Een avond in het Praatdeurtjesbos'));
    $story['character'] = (string) ($story['character'] ?? 'Mosje');
    $story['new_characters'] = is_array($story['new_characters'] ?? null) ? $story['new_characters'] : array();
    $story['new_places'] = is_array($story['new_places'] ?? null) ? $story['new_places'] : array();
    $story = pd_clean_story($story);

    // v0.29: dag-avontuur — titel blijft schoon (zelfde basis voor alle 3 delen);
    // het deel-nummer leeft alleen nog in post-meta + het "Dit avontuur in delen"-blokje.
    if ($arcs_on && !empty($arc)) {
        $base = (1 === $part) ? (string) $story['title'] : (string) ($arc['title_base'] ?: $story['title']);
        $story['title'] = $base;
        $story['arc'] = array('day_id' => (int) $arc['day_id'], 'part' => $part, 'total' => 3, 'daypart' => $dd_cur);
        pd_arc_record($base, (string) ($story['summary'] ?? ''));
    }
    return $story;
}

/* ---- 2) gpt-image-1 illustratie met referentiebeelden -> echte JPEG via GD ---- */

/** Karakters -> herkenningswoorden in scènetekst (NL) en beeldbeschrijving (EN).
 *  Basis hardcoded; canon-personages (zoals Pip) komen er automatisch bij op naam + evt. 'keywords'. */
function pd_character_keywords(): array {
    $map = array(
        'mosje'      => array('mosje', 'gnome', 'kabouter'),
        'kwakkel'    => array('kwakkel', 'duck', 'eend'),
        'nachtbloem' => array('nachtbloem', 'nightflower', 'night flower', 'singing flower', 'zingende bloem', 'glowing flower'),
    );
    foreach (pd_canon()['characters'] as $ch) {
        if (empty($ch['name']) || pd_name_covers_base((string) $ch['name'])) { continue; }
        $slug = pd_slugify((string) $ch['name']);
        if ('' === $slug || isset($map[$slug])) { continue; }
        $words = array(mb_strtolower(trim((string) $ch['name']), 'UTF-8'));
        foreach ((array) ($ch['keywords'] ?? array()) as $w) {
            $w = mb_strtolower(trim((string) $w), 'UTF-8');
            if ('' !== $w) { $words[] = $w; }
        }
        $map[$slug] = array_values(array_unique($words));
    }
    return $map;
}

/** Komt dit woord in de tekst voor? Korte woorden (<= 3 tekens, zoals "Pip") alleen als los woord. */
function pd_haystack_has(string $h, string $w): bool {
    if (mb_strlen($w, 'UTF-8') <= 3) {
        return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/u', $h);
    }
    return false !== strpos($h, $w);
}

/**
 * Cast van een scène: voorkeur = wat de schrijver EXPLICIET opgeeft
 * (scenes[].characters / scenes[].places, sinds v0.12) — geen trefwoord-raden
 * meer ("a happy dog" triggerde personage Happy). Fallback: oude detectie
 * (wachtrij-items van vóór v0.12 hebben de velden niet).
 * Geeft array(character_slugs[], place_slugs[]).
 */
function pd_scene_cast(array $sc): array {
    $chars = array(); $places = array();
    foreach ((array) ($sc['characters'] ?? array()) as $n) {
        $s = pd_canon_slug(trim((string) $n));
        if ('' !== $s) { $chars[] = $s; }
    }
    foreach ((array) ($sc['places'] ?? array()) as $n) {
        $s = pd_slugify(trim((string) $n));
        if ('' !== $s) { $places[] = $s; }
    }
    $haystack = ((string) ($sc['text'] ?? '')) . ' ' . ((string) ($sc['image'] ?? ''));
    if (!$chars)  { $chars = pd_detect_characters($haystack); }
    if (!$places) { $places = pd_detect_places($haystack); }
    return array(array_values(array_unique($chars)), array_values(array_unique($places)));
}

/**
 * Vaste visuele regels (props + verboden) van de aanwezige cast — deterministisch
 * uit de canon opgebouwd, zodat consistentie niet afhangt van wat gpt-4o in het
 * continuity-veld zet. Voorbeeld props bij Belle: "one small bright red ball,
 * Belle's ball, appears only when Belle plays". Verboden: "two balls; brown fur".
 */
function pd_visual_rules(array $char_slugs, array $place_slugs): string {
    $canon = pd_canon();
    $lines = array();
    foreach ((array) $canon['characters'] as $ch) {
        if (empty($ch['name']) || !in_array(pd_canon_slug((string) $ch['name']), $char_slugs, true)) { continue; }
        if (!empty($ch['props']))    { $lines[] = $ch['name'] . '\'s own things (they appear ONLY when ' . $ch['name'] . ' is in the scene; friends may interact with them while ' . $ch['name'] . ' is present): ' . rtrim((string) $ch['props'], '. ') . '.'; }
        if (!empty($ch['verboden'])) { $lines[] = 'NEVER show for ' . $ch['name'] . ': ' . rtrim((string) $ch['verboden'], '. ') . '.'; }
    }
    foreach ((array) $canon['places'] as $pl) {
        if (empty($pl['name']) || !in_array(pd_slugify((string) $pl['name']), $place_slugs, true)) { continue; }
        if (!empty($pl['props']))    { $lines[] = $pl['name'] . ' fixed details: ' . rtrim((string) $pl['props'], '. ') . '.'; }
        if (!empty($pl['verboden'])) { $lines[] = 'NEVER show at ' . $pl['name'] . ': ' . rtrim((string) $pl['verboden'], '. ') . '.'; }
    }
    return $lines ? ('FIXED VISUAL RULES (canon, always obey): ' . implode(' ', $lines) . ' ') : '';
}

/** Welke vaste karakters komen in deze scène voor? Fallback: mosje (stijlanker). */
function pd_detect_characters(string $haystack): array {
    $found = array();
    $h = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
    foreach (pd_character_keywords() as $slug => $words) {
        foreach ($words as $w) {
            if (pd_haystack_has($h, $w)) { $found[] = $slug; break; }
        }
    }
    return $found ?: array('mosje');
}

/** Welke canon-plekken komen in deze scène voor? (alleen relevant als er een ref-plek-<slug>-*.png bestaat) */
function pd_detect_places(string $haystack): array {
    $found = array();
    $h = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
    foreach (pd_canon()['places'] as $pl) {
        if (empty($pl['name'])) { continue; }
        $name = mb_strtolower(trim((string) $pl['name']), 'UTF-8');
        $bare = preg_replace('/^(de|het|een)\s+/u', '', $name); // "het meertje" -> "meertje"
        if (pd_haystack_has($h, $name) || ('' !== $bare && pd_haystack_has($h, $bare))) {
            $found[] = pd_slugify((string) $pl['name']);
        }
    }
    return array_values(array_unique($found));
}

/**
 * v0.18: seizoensvariant van een plek-ref — rouleert vanzelf mee met de meteorologische
 * seizoenswissels (1 mrt / 1 jun / 1 sep / 1 dec, zelfde kalender als het verhaal-weer).
 * De basis-ref is het zomerbeeld; lente/herfst/winter worden just-in-time éénmalig
 * gegenereerd VANUIT de basis-ref (zelfde plek, zelfde deurdetails, alleen het seizoen
 * verandert) zodra een plek in dat seizoen voorkomt. Bij een fout: stil terug naar de basis-ref.
 */
function pd_seizoens_plekref(string $slug, string $seizoen = ''): string {
    if ('' === $seizoen) { $seizoen = pd_seizoen(); } // expliciet seizoen: vooruit genereren via de admin (v0.19)
    if ('zomer' === $seizoen) { return ''; } // basis-ref ís het zomerbeeld
    $file = PD_DIR . '/ref-plekseizoen-' . $seizoen . '-' . $slug . '-1.png';
    if (file_exists($file)) { return $file; }
    $base = pd_ref_file('place', $slug);
    if ('' === $base) { return ''; } // geen basis-ref: niets om te variëren
    $key = (string) pd_get('pd_openai_api_key');
    if ('' === $key) { return ''; }
    $en = '';
    foreach ((array) (pd_canon()['places'] ?? array()) as $pl) {
        if (pd_slugify((string) ($pl['name'] ?? '')) === $slug) { $en = trim((string) ($pl['beschrijving_en'] ?? '')); break; }
    }
    $sfeer = array(
        'lente'  => 'early spring: fresh light greens, budding leaves, small spring flowers and blossoms, soft clear spring light',
        'herfst' => 'autumn: warm golden and orange leaves (some falling), a few small mushrooms, soft hazy autumn light',
        'winter' => 'winter: bare branches, a thin soft layer of snow on roofs branches and the ground, calm cosy winter light, no leaves',
    );
    $prompt = 'Redraw EXACTLY this same place in ' . ($sfeer[$seizoen] ?? $seizoen) . '. '
        . ('' !== $en ? 'The place: ' . rtrim($en, '. ') . '. ' : '')
        . 'Keep the place IDENTICAL to the reference: same door and built details, same colours of all built elements, same composition and viewpoint, same drawing style (soft watercolor and colored pencil children\'s book illustration). Change ONLY the vegetation, light and weather to match the season. No characters, no text, letters or words anywhere.';
    $b64 = pd_openai_image($key, $prompt, array($base));
    if (is_wp_error($b64)) {
        pd_log('Seizoensref mislukt (' . $seizoen . '/' . $slug . '): ' . $b64->get_error_message());
        return '';
    }
    file_put_contents($file, base64_decode((string) $b64));
    pd_log('Seizoensref gemaakt: ' . basename($file) . '.');
    return $file;
}

/**
 * Referentiebeelden voor deze karakters: losse refs (ref-<naam>-*.png), plus een
 * set-ref (ref-set-<a>+<b>-*.png, alfabetisch) als die voor exact deze combinatie
 * bestaat. Legacy mosje-ref-*.png telt als mosje. Daarna evt. plek-refs
 * (ref-plek-<naam>-*.png) als er nog ruimte is. Max 3 beelden.
 */
function pd_reference_images(array $slugs, array $place_slugs = array(), string $indoor_slug = ''): array {
    $refs = array();
    if (count($slugs) > 1) {
        $combo = $slugs; sort($combo);
        $set = glob(PD_DIR . '/ref-set-' . implode('+', $combo) . '-*.png') ?: array();
        sort($set);
        if ($set) { $refs[] = $set[0]; }
    }
    foreach ($slugs as $slug) {
        $files = glob(PD_DIR . '/ref-' . $slug . '-*.png') ?: array();
        if (!$files && 'mosje' === $slug) { $files = glob(PD_DIR . '/mosje-ref-*.png') ?: array(); }
        sort($files);
        if ($files) { $refs[] = $files[0]; }
    }
    foreach ($place_slugs as $slug) { // plekken pas na de karakters (karakter-consistentie weegt zwaarder)
        if ('' !== $indoor_slug && $slug === $indoor_slug) { // v0.21: binnen-scène -> binnenkant-ref i.p.v. buitenkant
            $binnen = glob(PD_DIR . '/ref-plekbinnen-' . $slug . '-*.png') ?: array();
            sort($binnen);
            if ($binnen) { $refs[] = $binnen[0]; continue; }
            // geen binnen-ref: dan liever GEEN buiten-ref meesturen (anders tekent het
            // model het huisje van buiten met een open muur) — de EN-beschrijving stuurt.
            continue;
        }
        $sref = pd_seizoens_plekref($slug); // v0.18: seizoensvariant als die er is/gemaakt kan worden
        if ('' !== $sref) { $refs[] = $sref; continue; }
        $files = glob(PD_DIR . '/ref-plek-' . $slug . '-*.png') ?: array();
        sort($files);
        if ($files) { $refs[] = $files[0]; }
    }
    // v0.14.1: poseblad van het éérste (hoofd)personage meesturen als er ruimte is —
    // leert de tekenaar dat het karakter mag bewegen (identiteit vast, pose vrij).
    $refs = array_slice(array_values(array_unique($refs)), 0, 3);
    if (count($refs) < 3 && !empty($slugs)) {
        $pb = glob(PD_DIR . '/poseblad-' . $slugs[0] . '-*.png') ?: array();
        sort($pb);
        if ($pb) { $refs[] = $pb[0]; }
    }
    return array_slice($refs, 0, 3);
}

/* ====================================================================
 * AUTOMATISCHE REFERENTIEBEELDEN — introduceert een aflevering een nieuw
 * personage of nieuwe plek, dan maken we daar na publicatie (los event,
 * PD_MAKE_REFS) een schoon referentiebeeld van: ref-<slug>-1.png resp.
 * ref-plek-<slug>-1.png. Bron = de scènetekening uit de aflevering zelf
 * waarin het figuur voorkomt, zodat de referentie precies overeenkomt
 * met hoe het er in de video uitzag. Handmatig bijmaken: ?pd_refs=pd-refs-Mosje42.
 * ==================================================================== */

/** Canonieke slug voor een canon-naam: basiskarakters houden hun vaste korte slug. */
function pd_canon_slug(string $name): string {
    $n = mb_strtolower($name, 'UTF-8');
    foreach (array('mosje', 'kwakkel', 'nachtbloem') as $base) {
        if (false !== strpos($n, $base)) { return $base; }
    }
    return pd_slugify($name);
}

/** Eerste bestaande ref-bestand voor deze entry, of '' als er nog geen is. */
function pd_ref_file(string $kind, string $slug): string {
    $prefix = ('place' === $kind) ? 'ref-plek-' : 'ref-';
    $files = glob(PD_DIR . '/' . $prefix . $slug . '-*.png') ?: array();
    if (!$files && 'mosje' === $slug) { $files = glob(PD_DIR . '/mosje-ref-*.png') ?: array(); }
    sort($files);
    return $files ? $files[0] : '';
}

/** Jobs voor zojuist toegevoegde personages/plekken; koppelt elk aan de scène waarin het voorkomt. */
function pd_ref_jobs_for_added(array $added, array $story, array $images): array {
    $canon = pd_canon();
    $jobs  = array();
    $find_scene = function (string $name) use ($story, $images): string {
        $n    = mb_strtolower(trim($name), 'UTF-8');
        $bare = preg_replace('/^(de|het|een)\s+/u', '', $n);
        foreach ((array) ($story['scenes'] ?? array()) as $i => $sc) {
            $h = mb_strtolower(((string) ($sc['text'] ?? '')) . ' ' . ((string) ($sc['image'] ?? '')), 'UTF-8');
            if (pd_haystack_has($h, $n) || ('' !== $bare && pd_haystack_has($h, $bare))) {
                $local = (string) ($images[$i]['local'] ?? '');
                if ('' !== $local && file_exists($local)) { return $local; }
            }
        }
        return '';
    };
    foreach ((array) ($added['characters'] ?? array()) as $name) {
        foreach ($canon['characters'] as $ch) {
            if (mb_strtolower(trim((string) ($ch['name'] ?? '')), 'UTF-8') !== mb_strtolower(trim((string) $name), 'UTF-8')) { continue; }
            $slug = pd_canon_slug((string) $name);
            if ('' === $slug || '' !== pd_ref_file('character', $slug)) { break; }
            $jobs[] = array('kind' => 'character', 'slug' => $slug, 'name' => (string) $name, 'desc' => (string) (!empty($ch['uiterlijk_en']) ? $ch['uiterlijk_en'] : ($ch['uiterlijk'] ?? '')), 'src' => $find_scene((string) $name));
            break;
        }
    }
    foreach ((array) ($added['places'] ?? array()) as $name) {
        foreach ($canon['places'] as $pl) {
            if (mb_strtolower(trim((string) ($pl['name'] ?? '')), 'UTF-8') !== mb_strtolower(trim((string) $name), 'UTF-8')) { continue; }
            $slug = pd_slugify((string) $name);
            if ('' === $slug || '' !== pd_ref_file('place', $slug)) { break; }
            $jobs[] = array('kind' => 'place', 'slug' => $slug, 'name' => (string) $name, 'desc' => (string) (!empty($pl['beschrijving_en']) ? $pl['beschrijving_en'] : ($pl['beschrijving'] ?? '')), 'src' => $find_scene((string) $name));
            break;
        }
    }
    return $jobs;
}

/** v0.12: jobs voor cast-leden van DIT verhaal die nog geen referentiebeeld hebben
 *  (gedraaid in fase A, vóór de scènebeelden). Bron = canon-beschrijving; stijlanker = Mosje-ref. */
function pd_fase_a_ref_jobs(array $story): array {
    $canon = pd_canon();
    $jobs = array(); $seen = array();
    foreach ((array) ($story['scenes'] ?? array()) as $sc) {
        list($chars, $places) = pd_scene_cast((array) $sc);
        foreach ($chars as $slug) {
            if (isset($seen['c' . $slug]) || '' !== pd_ref_file('character', $slug)) { continue; }
            foreach ($canon['characters'] as $ch) {
                if (pd_canon_slug((string) ($ch['name'] ?? '')) !== $slug) { continue; }
                $desc = (string) (!empty($ch['uiterlijk_en']) ? $ch['uiterlijk_en'] : ($ch['uiterlijk'] ?? ''));
                if ('' !== $desc) { $jobs[] = array('kind' => 'character', 'slug' => $slug, 'name' => (string) $ch['name'], 'desc' => $desc, 'src' => ''); }
                break;
            }
            $seen['c' . $slug] = 1;
        }
        foreach ($places as $slug) {
            if (isset($seen['p' . $slug]) || '' !== pd_ref_file('place', $slug)) { continue; }
            foreach ($canon['places'] as $pl) {
                if (pd_slugify((string) ($pl['name'] ?? '')) !== $slug) { continue; }
                $desc = (string) (!empty($pl['beschrijving_en']) ? $pl['beschrijving_en'] : '');
                if ('' !== $desc) { $jobs[] = array('kind' => 'place', 'slug' => $slug, 'name' => (string) $pl['name'], 'desc' => $desc, 'src' => ''); }
                break;
            }
            $seen['p' . $slug] = 1;
        }
    }
    return $jobs;
}

/** Jobs voor canon-entries die nog géén ref hebben (handmatige trigger). Personages altijd;
 *  plekken alleen als ze een Engelse beschrijving hebben (= via de nieuwe flow toegevoegd). */
function pd_missing_ref_jobs(): array {
    $canon = pd_canon();
    $jobs  = array();
    $base_locks = pd_base_locks();
    foreach ($canon['characters'] as $ch) {
        if (empty($ch['name'])) { continue; }
        $slug = pd_canon_slug((string) $ch['name']);
        if ('' === $slug || '' !== pd_ref_file('character', $slug)) { continue; }
        $desc = (string) (!empty($ch['uiterlijk_en']) ? $ch['uiterlijk_en'] : ($base_locks[$slug] ?? ($ch['uiterlijk'] ?? '')));
        $jobs[] = array('kind' => 'character', 'slug' => $slug, 'name' => (string) $ch['name'], 'desc' => $desc, 'src' => '');
    }
    foreach ($canon['places'] as $pl) {
        if (empty($pl['name']) || empty($pl['beschrijving_en'])) { continue; }
        $slug = pd_slugify((string) $pl['name']);
        if ('' === $slug || '' !== pd_ref_file('place', $slug)) { continue; }
        $jobs[] = array('kind' => 'place', 'slug' => $slug, 'name' => (string) $pl['name'], 'desc' => (string) $pl['beschrijving_en'], 'src' => '');
    }
    return $jobs;
}

/** Maakt de referentiebeelden (max 2 per run, rest wordt opnieuw ingepland). */
function pd_make_refs($jobs) {
    @set_time_limit(280);
    if (!is_array($jobs) || !$jobs) { return array('idle' => 'geen ref-jobs'); }
    if (!is_dir(PD_DIR)) { wp_mkdir_p(PD_DIR); }
    $key = (string) pd_get('pd_openai_api_key');
    if ('' === $key) { pd_log('Ref-generatie afgebroken: OpenAI-key ontbreekt.'); return array('error' => 'geen key'); }
    $batch = array_slice($jobs, 0, 2);
    $rest  = array_slice($jobs, 2);
    $done  = array();
    foreach ($batch as $job) {
        $kind = (string) ($job['kind'] ?? 'character');
        $slug = (string) ($job['slug'] ?? '');
        if ('' === $slug) { continue; }
        if ('' !== pd_ref_file($kind, $slug)) { $done[] = $slug . ' (bestond al)'; continue; }
        $name = (string) ($job['name'] ?? $slug);
        $desc = trim((string) ($job['desc'] ?? ''));
        $src  = (string) ($job['src'] ?? '');
        $from_scene = ('' !== $src && file_exists($src));
        // Zonder eigen scène: Mosje-ref als stijlanker (alleen stijl, niet het figuur zelf).
        $refs = $from_scene ? array($src) : pd_reference_images(array('mosje'));
        if ('character' === $kind) {
            $prompt = 'Children\'s picture-book character reference sheet in the exact same soft watercolour / coloured-pencil storybook style as the reference image. Draw ONLY this one character'
                . ($from_scene ? ', exactly as it appears in the reference image' : '') . ': ' . $name
                . ('' !== $desc ? (' — ' . rtrim($desc, '. ')) : '') . '. '
                . 'Full body, friendly neutral pose, centered and large in frame, on a plain very light cream background. No scenery, no props, no other characters'
                . ($from_scene ? '' : ' (do NOT draw the gnome from the style reference)') . ', no text anywhere in the image.';
        } else {
            $prompt = 'Children\'s picture-book location reference in the exact same soft watercolour / coloured-pencil storybook style as the reference image. Draw ONLY this one place'
                . ($from_scene ? ', exactly as it appears in the reference image' : '') . ': ' . $name
                . ('' !== $desc ? (' — ' . rtrim($desc, '. ')) : '') . '. '
                . 'Clear full view of the place in soft fresh daylight, no characters, no text anywhere in the image.';
        }
        $b64 = pd_openai_image($key, $prompt, $refs);
        if (is_wp_error($b64)) { pd_log('Ref-generatie mislukt voor "' . $name . '": ' . $b64->get_error_message()); continue; }
        $file = PD_DIR . '/' . (('place' === $kind) ? 'ref-plek-' : 'ref-') . $slug . '-1.png';
        file_put_contents($file, base64_decode((string) $b64));
        pd_log('Referentiebeeld gemaakt: ' . basename($file) . ' (' . $name . ').');
        $done[] = basename($file);
    }
    if ($rest) { wp_schedule_single_event(time() + 60, PD_MAKE_REFS, array($rest)); }
    return array('done' => $done, 'rest' => count($rest));
}

/* ---- QA-check na generatie (v0.15): gpt-4o-mini kijkt of het beeld klopt ----
 * Bewust SMAL gehouden (alleen harde fouten: personage dubbel/ontbreekt, leesbare
 * tekst, meer dan één bal) om valse afkeuringen en kosten te voorkomen.
 * Uit te zetten met optie pd_image_check = 0. Max 1 herkansing per scène,
 * max 2 per aflevering. */
function pd_image_check(string $key, string $local, array $char_slugs) {
    if ('1' !== (string) (pd_get('pd_image_check') ?: '1')) { return array('ok' => true); }
    if ('' === $local || !file_exists($local) || !function_exists('base64_encode')) { return array('ok' => true); }
    $names = array();
    foreach (pd_canon()['characters'] as $ch) {
        if (!empty($ch['name']) && in_array(pd_canon_slug((string) $ch['name']), $char_slugs, true)) { $names[] = (string) $ch['name']; }
    }
    $names_txt = $names ? implode(', ', $names) : 'the main character';
    $q = "Check this children's book illustration. Characters that must each appear EXACTLY once: {$names_txt}. "
       . 'Answer ONLY JSON: {"ok": boolean, "problems": string[], "retry_direction": string (short English instruction to fix the image, empty if ok)}. '
       . 'Mark ok=false ONLY for these hard errors: a character appearing more than once in the image; a required character clearly missing; readable text or letters anywhere; more than one ball. Otherwise ok=true (style and small details are fine).';
    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 90,
        'headers' => array('Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array(
            'model' => 'gpt-4o-mini', 'max_tokens' => 200,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(array('role' => 'user', 'content' => array(
                array('type' => 'text', 'text' => $q),
                array('type' => 'image_url', 'image_url' => array('url' => 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($local)), 'detail' => 'low')),
            ))),
        )),
    ));
    if (is_wp_error($resp) || 200 !== wp_remote_retrieve_response_code($resp)) { return array('ok' => true); } // check-fout = doorlaten
    $out = json_decode((string) (json_decode(wp_remote_retrieve_body($resp), true)['choices'][0]['message']['content'] ?? ''), true);
    if (!is_array($out)) { return array('ok' => true); }
    return array('ok' => !empty($out['ok']), 'problems' => (array) ($out['problems'] ?? array()), 'retry_direction' => (string) ($out['retry_direction'] ?? ''));
}

/* ---- Beeldregie (v0.13): visual direction + anti-herhalingsgeheugen ---- */
function pd_visual_log_add(string $sig): void {
    $sig = trim(mb_substr($sig, 0, 140, 'UTF-8'));
    if ('' === $sig) { return; }
    $log = (array) pd_get('pd_visual_log', array());
    array_unshift($log, $sig);
    pd_set('pd_visual_log', array_slice($log, 0, 15));
}

/** Bouwt het CHARACTER POSES-blok uit scenes[].character_poses (v0.14). */
function pd_pose_text(?array $poses): string {
    if (!is_array($poses) || empty($poses)) { return ''; }
    $lines = array();
    foreach ($poses as $p) {
        if (empty($p['name'])) { continue; }
        $bits = array_filter(array((string) ($p['view_angle'] ?? ''), (string) ($p['pose'] ?? ''), (string) ($p['gesture'] ?? ''), (string) ($p['expression'] ?? '')));
        if ($bits) { $lines[] = (string) $p['name'] . ': ' . implode(', ', $bits); }
    }
    if (!$lines) { return ''; }
    return 'CHARACTER POSES for this image (follow exactly): ' . implode('. ', $lines) . '. ';
}

/** Bouwt het VISUAL DIRECTION-blok voor de beeldprompt uit scenes[].visual_direction. */
function pd_direction_text(?array $vd): string {
    if (!is_array($vd) || empty($vd)) { return ''; }
    $parts = array();
    if (!empty($vd['composition_type']))   { $parts[] = 'composition type: ' . $vd['composition_type']; }
    if (!empty($vd['main_visual_focus']))  { $parts[] = 'main visual focus: ' . $vd['main_visual_focus']; }
    if (!empty($vd['camera_angle']))       { $parts[] = 'camera angle: ' . $vd['camera_angle']; }
    if (!empty($vd['shot_size']))          { $parts[] = 'shot size: ' . $vd['shot_size']; }
    if (!empty($vd['character_placement'])){ $parts[] = 'character placement: ' . $vd['character_placement']; }
    if (!$parts) { return ''; }
    return 'VISUAL DIRECTION for this image (follow strictly): ' . implode('; ', $parts) . '. Never default to one character standing on the left and another on the right facing each other. ';
}

function pd_generate_image(string $key, string $scene_image, string $stamp, int $n, string $scene_text = '', string $continuity = '', ?array $cast = null, ?array $direction = null, ?array $poses = null, string $indoor = '') {
    if (is_array($cast) && count($cast) === 2) {
        list($present, $places) = $cast; // expliciete cast uit het verhaal (v0.12)
    } else {
        $present = pd_detect_characters($scene_text . ' ' . $scene_image);
        $places  = pd_detect_places($scene_text . ' ' . $scene_image);
    }
    // v0.21: binnen-scène — binnenkant-ref + interieur-regels (nooit een huisje zonder muur).
    $indoor_slug = '' !== trim($indoor) ? pd_slugify(trim($indoor)) : '';
    $indoor_txt  = '';
    if ('' !== $indoor_slug) {
        if (!in_array($indoor_slug, $places, true)) { $places[] = $indoor_slug; }
        $binnen_en = '';
        foreach ((array) (pd_canon()['places'] ?? array()) as $pl) {
            if (pd_slugify((string) ($pl['name'] ?? '')) === $indoor_slug) { $binnen_en = trim((string) ($pl['binnen_en'] ?? '')); break; }
        }
        $indoor_txt = 'INTERIOR SCENE: this scene takes place INSIDE ' . trim($indoor) . '. Show the cosy interior from WITHIN the room, as if the viewer is inside too. '
            . ('' !== $binnen_en ? ('The interior: ' . rtrim($binnen_en, '. ') . '. ') : '')
            . 'Never show the building from outside, never a cutaway or dollhouse view with a missing wall; walls, ceiling or roof curve naturally around the scene. A window may show the weather outside. ';
    }
    $refs    = pd_reference_images($present, $places, $indoor_slug);
    // Continuïteit over de 5 scènes heen: vaste prop-kleuren/-aantallen, seizoen en
    // tijd van de dag (2026-06-05: bal wisselde van rood naar groen en verdubbelde).
    $cont = '' !== trim($continuity)
        ? 'CONTINUITY (these details are identical in every scene of this story, keep them exactly): ' . rtrim(trim($continuity), '. ') . '. '
        : '';
    $has_poseblad = false;
    foreach ($refs as $r) { if (0 === strpos(basename((string) $r), 'poseblad-')) { $has_poseblad = true; break; } }
    $prompt = 'Children\'s picture-book illustration in a soft watercolour / coloured-pencil storybook style; cosy, gentle and tender fairytale mood; soft, fresh and well-balanced natural colours (NOT a heavy yellow or sepia tint, not muddy and not dark); a mossy forest world. '
        . pd_image_character_lock($present)
        . pd_visual_rules($present, $places)
        . $indoor_txt
        . pd_direction_text($direction)
        . pd_pose_text($poses)
        . ($has_poseblad ? 'One reference is a POSE SHEET showing the same character in several poses — use it ONLY to understand the character\'s views and movement; draw that character exactly ONCE in this image, in the requested pose (never multiple copies). ' : '')
        . $cont
        . 'IMPORTANT: use the reference image(s) ONLY to preserve character identity (appearance, clothing, colours and proportions) and the general drawing style. Do NOT copy the pose, camera angle or composition from the reference — show each character in the specific pose and view requested for this scene. Do NOT copy the reference\'s background, scenery, props, time of day or colour grading. Render exactly the setting, lighting and time of day described in THIS scene, and include ONLY the characters and objects this scene mentions (do not add extra characters, flowers, fireflies or little doors unless the scene asks for them). Show each mentioned object exactly ONCE — one single ball, never two — unless the scene explicitly asks for more. '
        . 'Scene: ' . $scene_image . ' No text, letters or words anywhere in the image.';
    $b64 = pd_openai_image($key, $prompt, $refs);
    if (is_wp_error($b64)) { return $b64; }
    $raw = base64_decode((string) $b64);
    $jpg = PD_DIR . '/scene-' . $stamp . '-' . $n . '.jpg';
    // Echte JPEG via GD (Shotstack ondersteunt geen webp; host remapt jpeg->webp bij WP-editor).
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $im = @imagecreatefromstring($raw);
        if ($im) { imagejpeg($im, $jpg, 82); imagedestroy($im); return array('local' => $jpg, 'url' => PD_URL_BASE . basename($jpg)); }
    }
    $png = PD_DIR . '/scene-' . $stamp . '-' . $n . '.png';
    file_put_contents($png, $raw);
    return array('local' => $png, 'url' => PD_URL_BASE . basename($png));
}

/* ---- Verbruiksteller (per maand, voor het tegoed-blok) ---- */
function pd_cost_add(string $field, int $n = 1): void {
    $month = gmdate('Y-m');
    $c = (array) pd_get('pd_cost_month', array());
    if (($c['month'] ?? '') !== $month) { $c = array('month' => $month); }
    $c[$field] = (int) ($c[$field] ?? 0) + $n;
    pd_set('pd_cost_month', $c);
}

/* v0.13: thumbnail als aparte POSTER van de aflevering — niet scène 1 hergebruiken.
   Het unieke onderwerp groot in beeld, compositie wisselt per aflevering. */
function pd_generate_thumbnail(string $key, array $t, string $stamp) {
    $char_slugs = array();
    foreach ((array) ($t['characters'] ?? array()) as $n) { $s = pd_canon_slug(trim((string) $n)); if ('' !== $s) { $char_slugs[] = $s; } }
    $char_slugs = array_slice(array_unique($char_slugs), 0, 2);
    $place_slugs = array();
    if (!empty($t['location'])) { $s = pd_slugify((string) $t['location']); if ('' !== $s) { $place_slugs[] = $s; } }
    $refs = pd_reference_images($char_slugs, $place_slugs);
    $prompt = 'YouTube thumbnail poster for a children\'s bedtime story episode, in a soft watercolour / coloured-pencil storybook style; warm, safe and calm like classic European children\'s books; bright and clear enough for a small thumbnail; soft fresh natural colours, not too yellow, not dark. '
        . pd_image_character_lock($char_slugs)
        . pd_visual_rules($char_slugs, $place_slugs)
        . 'THIS IS A POSTER, not a story scene: the unique subject of this episode must dominate the image. '
        . 'Main focus, LARGE and central: ' . (string) $t['main_focus'] . '. '
        . 'Composition type: ' . (string) ($t['composition_type'] ?? 'magic_object_focus') . '. '
        . (!empty($t['camera_angle']) ? ('Camera angle: ' . (string) $t['camera_angle'] . '. ') : '')
        . (!empty($t['mood']) ? ('Mood: ' . (string) $t['mood'] . '. ') : '')
        . (!empty($t['must_include']) ? ('Must include: ' . (string) $t['must_include'] . '. ') : '')
        . 'Do NOT use the standard composition of one character on the left and another on the right. At most two characters, and they may be small if the object or place is the star. '
        . (!empty($t['must_avoid']) ? ('Must avoid: ' . (string) $t['must_avoid'] . '. ') : '')
        . 'No text, letters or words anywhere in the image.';
    $b64 = pd_openai_image($key, $prompt, $refs);
    if (is_wp_error($b64)) { return $b64; }
    $raw = base64_decode((string) $b64);
    $jpg = PD_DIR . '/thumb-' . $stamp . '.jpg';
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $im = @imagecreatefromstring($raw);
        if ($im) { imagejpeg($im, $jpg, 85); imagedestroy($im); return array('local' => $jpg, 'url' => PD_URL_BASE . basename($jpg)); }
    }
    file_put_contents($jpg, $raw);
    return array('local' => $jpg, 'url' => PD_URL_BASE . basename($jpg));
}

/* gpt-image-1: met referentiebeelden (images/edits) voor karakter-consistentie; anders tekst-only (generations). */
function pd_openai_image(string $key, string $prompt, ?array $refs = null) {
    if (null === $refs) { // backwards-compat: oude aanroepen zonder selectie
        $refs = glob(PD_DIR . '/mosje-ref-*.png') ?: array();
        sort($refs);
    }
    $refs = array_slice($refs, 0, 3);
    if ($refs && function_exists('curl_init')) {
        $post = array('model' => 'gpt-image-1', 'prompt' => $prompt, 'size' => '1536x1024', 'quality' => 'medium', 'n' => '1');
        foreach (array_values($refs) as $i => $rp) {
            $ext  = strtolower((string) pathinfo($rp, PATHINFO_EXTENSION));
            $jpeg = in_array($ext, array('jpg', 'jpeg'), true);
            $post['image[' . $i . ']'] = new CURLFile($rp, $jpeg ? 'image/jpeg' : 'image/png', 'ref' . $i . ($jpeg ? '.jpg' : '.png'));
        }
        $ch = curl_init('https://api.openai.com/v1/images/edits');
        curl_setopt_array($ch, array(CURLOPT_POST => true, CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $key), CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180));
        $body = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if (200 === $code) {
            $b = json_decode((string) $body, true)['data'][0]['b64_json'] ?? '';
            if ('' !== $b) { pd_cost_add('images'); return $b; }
        }
        pd_log('images/edits faalde (HTTP ' . $code . '), terugval op generations: ' . mb_substr((string) $body, 0, 160, 'UTF-8'));
    }
    $resp = wp_remote_post('https://api.openai.com/v1/images/generations', array('timeout' => 180, 'headers' => array('Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('model' => 'gpt-image-1', 'prompt' => $prompt, 'size' => '1536x1024', 'quality' => 'medium', 'n' => 1))));
    if (is_wp_error($resp)) { return $resp; }
    if (200 !== wp_remote_retrieve_response_code($resp)) { return new WP_Error('pd_img_http', 'gpt-image-1 HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $b64 = json_decode(wp_remote_retrieve_body($resp), true)['data'][0]['b64_json'] ?? '';
    if ('' !== $b64) { pd_cost_add('images'); return $b64; }
    return new WP_Error('pd_img_empty', 'Geen beelddata.');
}

/* ---- 3) ElevenLabs voorleesstem (5 scènes, één call) + scène-eindtijden ---- */
function pd_elevenlabs(string $key, array $scenes, string $stamp) {
    $voice = (string) (pd_get('pd_voice_id') ?: 'gCJROUe9eMZaWlhNj1z0');
    $texts = array(); foreach ($scenes as $sc) { $texts[] = trim((string) $sc['text']); }
    $joined = implode("\n\n", $texts);
    $resp = wp_remote_post('https://api.elevenlabs.io/v1/text-to-speech/' . $voice . '/with-timestamps', array(
        'timeout' => 120, 'headers' => array('xi-api-key' => $key, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array('text' => $joined, 'model_id' => 'eleven_multilingual_v2', 'voice_settings' => array('stability' => 0.55, 'similarity_boost' => 0.8, 'style' => 0.2, 'use_speaker_boost' => true)), JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($resp)) { return $resp; }
    if (200 !== wp_remote_retrieve_response_code($resp)) { return new WP_Error('pd_tts_http', 'ElevenLabs HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['audio_base64'])) { return new WP_Error('pd_tts_empty', 'Geen audio.'); }
    $fname = 'voice-' . $stamp . '.mp3';
    file_put_contents(PD_DIR . '/' . $fname, base64_decode($data['audio_base64']));
    $ends = $data['alignment']['character_end_times_seconds'] ?? array();
    $total = !empty($ends) ? (float) end($ends) : 2.0;
    $scene_end = array(); $cum = 0; $cnt = count($ends);
    foreach ($texts as $i => $t) {
        $cum += mb_strlen($t, 'UTF-8');
        $idx = max(0, min($cum - 1, $cnt - 1));
        $scene_end[$i] = !empty($ends) ? (float) $ends[$idx] : ($total * ($i + 1) / count($texts));
        $cum += 2;
    }
    $scene_end[count($texts) - 1] = $total;
    return array('url' => PD_URL_BASE . $fname, 'local' => PD_DIR . '/' . $fname, 'duration' => $total, 'scene_end' => $scene_end);
}

/* ---- 4) Shotstack: insturen + ophalen (gescheiden) ---- */
function pd_shotstack_claim_name(string $kind, string $stamp): string {
    return 'pd_shot_once_' . sanitize_key($kind) . '_' . substr(hash('sha256', $stamp), 0, 24);
}

function pd_shotstack_claim(string $kind, string $stamp) {
    if ('' === $stamp) { return array('token' => ''); }
    $name = pd_shotstack_claim_name($kind, $stamp);
    $now = time();
    $claim = get_option($name, null);
    if (is_array($claim) && !empty($claim['render_id'])) {
        return array('render_id' => (string) $claim['render_id']);
    }
    if (is_array($claim)) {
        return new WP_Error('pd_render_busy', 'Deze Shotstack-render wordt al door een ander proces ingestuurd; geen tweede render gestart.');
    }

    $token = wp_generate_uuid4();
    if (!add_option($name, array('token' => $token, 'time' => $now, 'kind' => $kind, 'stamp' => $stamp), '', false)) {
        $claim = get_option($name, null);
        if (is_array($claim) && !empty($claim['render_id'])) {
            return array('render_id' => (string) $claim['render_id']);
        }
        return new WP_Error('pd_render_busy', 'Deze Shotstack-render wordt al door een ander proces ingestuurd; geen tweede render gestart.');
    }
    return array('token' => $token);
}

function pd_shotstack_claim_complete(string $kind, string $stamp, string $token, string $render_id): void {
    if ('' === $stamp || '' === $token) { return; }
    $name = pd_shotstack_claim_name($kind, $stamp);
    $claim = get_option($name, null);
    if (is_array($claim) && hash_equals((string) ($claim['token'] ?? ''), $token)) {
        update_option($name, array('render_id' => $render_id, 'time' => time(), 'kind' => $kind, 'stamp' => $stamp), false);
    }
}

function pd_shotstack_claim_release(string $kind, string $stamp, string $token): void {
    if ('' === $stamp || '' === $token) { return; }
    $name = pd_shotstack_claim_name($kind, $stamp);
    $claim = get_option($name, null);
    if (is_array($claim) && hash_equals((string) ($claim['token'] ?? ''), $token)) {
        delete_option($name);
    }
}

function pd_shotstack_submit(string $key, string $env, string $title, array $images, array $voice, string $stamp = '') {
    $claim = pd_shotstack_claim('main', $stamp);
    if (is_wp_error($claim)) { return $claim; }
    if (!empty($claim['render_id'])) {
        pd_log('Bestaande Shotstack-hoofdrender hergebruikt: ' . $claim['render_id'] . '.');
        return (string) $claim['render_id'];
    }
    $claim_token = (string) ($claim['token'] ?? '');
    $base = 'https://api.shotstack.io/edit/' . $env;
    $music = (string) pd_get('pd_soundtrack_url');
    if ('' === $music) { $music = 'https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/music/unminus/lit.mp3'; }
    $use_music = !in_array(strtolower($music), array('none', 'geen', '0'), true); // 'none' = geen muziek
    $dur = (float) $voice['duration']; $total = round($dur + PD_POST, 2);
    $effects = array('zoomIn', 'slideLeft', 'zoomOut', 'slideRight', 'zoomIn');
    $img_clips = array(); $prev = 0.0; $cnt = count($images);
    foreach ($images as $i => $img) {
        $end = isset($voice['scene_end'][$i]) ? (float) $voice['scene_end'][$i] : ($dur * ($i + 1) / $cnt);
        $len = ($i === $cnt - 1) ? round($total - $prev, 2) : round($end - $prev, 2);
        if ($len < 0.5) { $len = 0.5; }
        $img_clips[] = array('asset' => array('type' => 'image', 'src' => $img['url']), 'start' => round($prev, 2), 'length' => $len, 'fit' => 'cover', 'effect' => $effects[$i % count($effects)], 'transition' => array('in' => 'fade', 'out' => 'fade'));
        $prev = $end;
    }
    $title_css = "p{font-family:'Open Sans';color:#ffffff;font-size:64px;font-weight:700;text-align:center;line-height:1.25;margin:0;text-shadow:0 2px 12px rgba(0,0,0,0.55);}";
    $title_clip = array('asset' => array('type' => 'html', 'html' => '<p>' . esc_html($title) . '</p>', 'css' => $title_css, 'width' => 1500, 'height' => 400, 'background' => 'transparent'), 'start' => 0.4, 'length' => 3.4, 'position' => 'center', 'transition' => array('in' => 'fade', 'out' => 'fade'));
    $timeline = array(
        'background' => '#fdfbf7', // zachte lichte achtergrond (ochtend), niet donker
        'fonts' => array(array('src' => 'https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/fonts/OpenSans-Bold.ttf')),
        'tracks' => array(array('clips' => array($title_clip)), array('clips' => array(array('asset' => array('type' => 'audio', 'src' => $voice['url']), 'start' => PD_PRE, 'length' => round($dur, 2)))), array('clips' => $img_clips)),
    );
    if ($use_music) {
        $timeline['soundtrack'] = array('src' => $music, 'effect' => 'fadeOut', 'volume' => (float) (pd_get('pd_soundtrack_volume') ?: 0.03));
    }
    $edit = array(
        'timeline' => $timeline,
        // exclude=true: Shotstack host de output NIET (we halen 'm zelf op binnen 24u) -> hun opslag vult niet vol.
        'output' => array('format' => 'mp4', 'size' => array('width' => 1920, 'height' => 1080), 'fps' => 25, 'destinations' => array(array('provider' => 'shotstack', 'exclude' => true))),
    );
    $resp = wp_remote_post($base . '/render', array('timeout' => 60, 'headers' => array('x-api-key' => $key, 'Content-Type' => 'application/json'), 'body' => wp_json_encode($edit)));
    if (is_wp_error($resp)) { return $resp; }
    $status = wp_remote_retrieve_response_code($resp);
    if ($status < 200 || $status >= 300) {
        pd_shotstack_claim_release('main', $stamp, $claim_token);
        return new WP_Error('pd_render_http', 'Shotstack HTTP ' . $status . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8'));
    }
    $rid = json_decode(wp_remote_retrieve_body($resp), true)['response']['id'] ?? '';
    if ('' === $rid) { return new WP_Error('pd_render_submit', 'Geen render-id: ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    pd_shotstack_claim_complete('main', $stamp, $claim_token, (string) $rid);
    return (string) $rid;
}
/* Verticale Short (1080x1920): scène 1 beeld + scène 1 audio, max ~55s, titel-overlay. */
function pd_shotstack_submit_short(string $key, string $env, string $title, array $images, array $voice, string $stamp = '') {
    if ('0' === (string) pd_get('pd_shorts')) { return new WP_Error('pd_short_off', 'Shorts staan uit (optie pd_shorts=0).'); }
    if (empty($images[0]['url'])) { return new WP_Error('pd_short_img', 'Geen scène 1 beeld.'); }
    $claim = pd_shotstack_claim('short', $stamp);
    if (is_wp_error($claim)) { return $claim; }
    if (!empty($claim['render_id'])) {
        pd_log('Bestaande Shotstack-Short hergebruikt: ' . $claim['render_id'] . '.');
        return (string) $claim['render_id'];
    }
    $claim_token = (string) ($claim['token'] ?? '');
    $base = 'https://api.shotstack.io/edit/' . $env;
    $s1_end = isset($voice['scene_end'][0]) ? (float) $voice['scene_end'][0] : min(40.0, (float) $voice['duration']);
    $alen = min($s1_end, 55.0); // YouTube Short: ruim onder 60s blijven
    $total = round($alen + PD_POST, 2);
    // v0.20.1: het liggende scènebeeld NIET meer hard croppen naar 9:16 (dat kneep de
    // personages eruit) — het volledige beeld in het midden (contain), met hetzelfde
    // beeld gevuld+gedimd als achtergrond erachter (klassieke Shorts-opmaak).
    $bg_clip = array('asset' => array('type' => 'image', 'src' => $images[0]['url']), 'start' => 0, 'length' => $total, 'fit' => 'cover', 'effect' => 'zoomInSlow', 'opacity' => 0.45, 'filter' => 'muted');
    $fg_clip = array('asset' => array('type' => 'image', 'src' => $images[0]['url']), 'start' => 0, 'length' => $total, 'fit' => 'contain', 'effect' => 'zoomInSlow', 'transition' => array('in' => 'fade', 'out' => 'fade'));
    $title_css = "p{font-family:'Open Sans';color:#ffffff;font-size:58px;font-weight:700;text-align:center;line-height:1.25;margin:0;text-shadow:0 2px 12px rgba(0,0,0,0.6);}";
    $title_clip = array('asset' => array('type' => 'html', 'html' => '<p>' . esc_html($title) . '</p>', 'css' => $title_css, 'width' => 900, 'height' => 400, 'background' => 'transparent'), 'start' => 0.4, 'length' => 3.2, 'position' => 'center', 'transition' => array('in' => 'fade', 'out' => 'fade'));
    $cta_css = "p{font-family:'Open Sans';color:#ffffff;font-size:42px;font-weight:700;text-align:center;line-height:1.3;margin:0;text-shadow:0 2px 12px rgba(0,0,0,0.6);}";
    $cta_clip = array('asset' => array('type' => 'html', 'html' => '<p>Luister het hele verhaaltje op ons kanaal 🌙</p>', 'css' => $cta_css, 'width' => 900, 'height' => 300, 'background' => 'transparent'), 'start' => max(0.5, $total - 3.0), 'length' => 2.8, 'position' => 'bottom', 'offset' => array('y' => 0.08), 'transition' => array('in' => 'fade', 'out' => 'fade'));
    $timeline = array(
        'background' => '#fdfbf7',
        'fonts' => array(array('src' => 'https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/fonts/OpenSans-Bold.ttf')),
        'tracks' => array(
            array('clips' => array($title_clip, $cta_clip)),
            array('clips' => array(array('asset' => array('type' => 'audio', 'src' => $voice['url'], 'trim' => 0, 'volume' => 1, 'effect' => 'fadeOut'), 'start' => PD_PRE, 'length' => round($alen, 2)))),
            array('clips' => array($fg_clip)),
            array('clips' => array($bg_clip)),
        ),
    );
    $edit = array(
        'timeline' => $timeline,
        'output' => array('format' => 'mp4', 'size' => array('width' => 1080, 'height' => 1920), 'fps' => 25, 'destinations' => array(array('provider' => 'shotstack', 'exclude' => true))),
    );
    $resp = wp_remote_post($base . '/render', array('timeout' => 60, 'headers' => array('x-api-key' => $key, 'Content-Type' => 'application/json'), 'body' => wp_json_encode($edit)));
    if (is_wp_error($resp)) { return $resp; }
    $status = wp_remote_retrieve_response_code($resp);
    if ($status < 200 || $status >= 300) {
        pd_shotstack_claim_release('short', $stamp, $claim_token);
        return new WP_Error('pd_short_http', 'Shotstack HTTP ' . $status . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8'));
    }
    $rid = json_decode(wp_remote_retrieve_body($resp), true)['response']['id'] ?? '';
    if ('' === $rid) { return new WP_Error('pd_short_submit', 'Geen short-render-id: ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    pd_shotstack_claim_complete('short', $stamp, $claim_token, (string) $rid);
    return (string) $rid;
}

function pd_shotstack_poll(string $key, string $env, string $rid): array {
    $base = 'https://api.shotstack.io/edit/' . $env;
    for ($i = 0; $i < 8; $i++) {
        $st = wp_remote_get($base . '/render/' . $rid, array('timeout' => 30, 'headers' => array('x-api-key' => $key)));
        if (!is_wp_error($st)) {
            $r = json_decode(wp_remote_retrieve_body($st), true)['response'] ?? array();
            $status = $r['status'] ?? '';
            if ('done' === $status) { return array('status' => 'done', 'url' => (string) ($r['url'] ?? '')); }
            if ('failed' === $status) { return array('status' => 'failed', 'url' => ''); }
        }
        if ($i < 7) { sleep(5); }
    }
    return array('status' => 'rendering', 'url' => '');
}

function pd_download(string $url, string $path): bool {
    // Streamen naar schijf i.p.v. de hele body in geheugen halen — een mp4 van
    // ~90MB via wp_remote_retrieve_body brak de 256MB-limiet (2026-06-05, Belle).
    if (function_exists('curl_init')) {
        $fh = fopen($path, 'wb');
        if ($fh) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_FILE => $fh, CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 600, CURLOPT_FAILONERROR => true,
            ));
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fh);
            if ($ok !== false && $code < 300 && filesize($path) > 0) { return true; }
            @unlink($path);
        }
    }
    // Fallback (kleine bestanden): buffer in geheugen.
    $resp = wp_remote_get($url, array('timeout' => 180));
    if (is_wp_error($resp) || 200 !== wp_remote_retrieve_response_code($resp)) { return false; }
    return (bool) file_put_contents($path, wp_remote_retrieve_body($resp));
}

/* ---- 5) YouTube — resumable upload (Gemaakt voor kinderen) + afspeellijst. $short = verticale Short. ---- */
function pd_post_youtube($local, array $story, int $ep, bool $short = false, string $thumb_local = '') {
    $refresh = (string) pd_get('dhs_pd_youtube_refresh_token');
    if ('' === $refresh) { return 'overgeslagen (geen YouTube-koppeling)'; }
    $cid = (string) pd_key('dhs_youtube_client_id'); $sec = (string) pd_key('dhs_youtube_client_secret');
    if ('' === $cid || '' === $sec) { return new WP_Error('pd_yt_cfg', 'Google client ontbreekt (blog 7).'); }
    if (!file_exists($local)) { return new WP_Error('pd_yt_file', 'mp4 niet gevonden'); }
    $tk = wp_remote_post('https://oauth2.googleapis.com/token', array('timeout' => 60, 'body' => array('client_id' => $cid, 'client_secret' => $sec, 'refresh_token' => $refresh, 'grant_type' => 'refresh_token')));
    if (is_wp_error($tk)) { return $tk; }
    $at = json_decode(wp_remote_retrieve_body($tk), true)['access_token'] ?? '';
    if ('' === $at) { return new WP_Error('pd_yt_token', 'Geen access-token (herautoriseren?)'); }
    $first = trim((string) ($story['scenes'][0]['text'] ?? ''));
    if ($short) {
        $desc  = "🌙 Het begin van een zacht slaapverhaaltje uit het Praatdeurtjesbos. Het hele verhaaltje staat op ons kanaal!\n\n"
            . "👉 Alle verhaaltjes: https://www.praatdeurtje.nl/category/verhalen/\n\n"
            . "#Shorts #slaapverhaaltje #voorlezen #kinderverhaal #welterusten #praatdeurtje";
        $title = mb_substr($story['title'] . ' 🌙 #Shorts', 0, 95, 'UTF-8');
    } else {
    // Eerste regels sterk (zoekresultaat toont ~150 tekens): waar gaat het over + voor wie.
    $summary = trim((string) ($story['summary'] ?? ''));
    if ('' === $summary) { $summary = $first; }
    $desc  = 'Een rustig slaapverhaaltje voor kinderen uit het Praatdeurtjesbos. ' . rtrim($summary, '. ') . ". Een zacht voorleesverhaal voor het slapengaan.\n\n"
        . "🌙 Alle slaapverhaaltjes: https://www.praatdeurtje.nl/category/verhalen/\n"
        . "🚪 Wie is Mosje? https://www.praatdeurtje.nl/wie-is-mosje/\n\n"
        . "Praatdeurtje maakt elke dag een nieuw, zacht voorleesverhaaltje: rustig voorgelezen, met lieve tekeningen. Fijn voor peuters en kleuters bij het slapengaan.\n\n"
        . "🎧 Ook als podcast! Zoek op \"Praatdeurtje slaapverhaaltjes\".\n\n"
        . "#slaapverhaaltje #voorleesverhaal #kinderen #bedtijd #praatdeurtje";
    $title = mb_substr($story['title'] . ' 🌙 Slaapverhaaltje voor kinderen | Praatdeurtje', 0, 95, 'UTF-8');
    }
    $snippet = array(
        'snippet' => array('title' => $title, 'description' => $desc, 'tags' => array('slaapverhaaltje', 'voorleesverhaal', 'kinderverhaal', 'verhaaltje voor het slapengaan', 'welterusten', 'kabouter', 'Mosje', 'Praatdeurtje', 'rustig verhaaltje', 'inslapen', 'sprookje', 'kinderen voorlezen', 'bedtijdverhaaltje'), 'categoryId' => '24', 'defaultLanguage' => 'nl', 'defaultAudioLanguage' => 'nl'),
        'status'  => array('privacyStatus' => (string) (pd_get('pd_yt_privacy') ?: 'public'), 'selfDeclaredMadeForKids' => true, 'embeddable' => true), // Gemaakt voor kinderen (COPPA); embeddable=true zodat de doorloop-speler werkt
    );
    $size = (int) filesize($local);
    $init = wp_remote_post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', array(
        'timeout' => 60, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json; charset=UTF-8', 'X-Upload-Content-Type' => 'video/mp4', 'X-Upload-Content-Length' => (string) $size), 'body' => wp_json_encode($snippet, JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($init)) { return $init; }
    if ((int) wp_remote_retrieve_response_code($init) >= 300) { return new WP_Error('pd_yt_init', 'init HTTP ' . wp_remote_retrieve_response_code($init) . ': ' . mb_substr(wp_remote_retrieve_body($init), 0, 200, 'UTF-8')); }
    $upload_url = (string) wp_remote_retrieve_header($init, 'location');
    if ('' === $upload_url || !function_exists('curl_init')) { return new WP_Error('pd_yt_loc', 'geen upload-url of geen cURL'); }
    $fh = fopen($local, 'rb'); $ch = curl_init($upload_url);
    curl_setopt_array($ch, array(CURLOPT_PUT => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => $size, CURLOPT_HTTPHEADER => array('Content-Type: video/mp4'), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 600));
    $body = curl_exec($ch); $pcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fh);
    $pdata = json_decode((string) $body, true);
    if ($pcode >= 300 || empty($pdata['id'])) { return new WP_Error('pd_yt_put', 'upload mislukt: ' . (is_array($pdata) ? ($pdata['error']['message'] ?? ('HTTP ' . $pcode)) : ('HTTP ' . $pcode))); }
    $vid = (string) $pdata['id'];
    // v0.13: custom thumbnail proberen (werkt pas na kanaalverificatie op youtube.com/verify)
    if (!$short && '' !== $thumb_local && file_exists($thumb_local) && function_exists('imagecreatefromstring')) {
        $im = @imagecreatefromstring((string) file_get_contents($thumb_local));
        if ($im) {
            $w = imagesx($im); $h = imagesy($im);
            $cw = $w; $chh = (int) round($w * 9 / 16);
            if ($chh > $h) { $chh = $h; $cw = (int) round($h * 16 / 9); }
            $out = imagecreatetruecolor(1280, 720);
            imagecopyresampled($out, $im, 0, 0, (int) (($w - $cw) / 2), (int) (($h - $chh) / 2), 1280, 720, $cw, $chh);
            $tmp = PD_DIR . '/yt-thumb-tmp.jpg';
            imagejpeg($out, $tmp, 88); imagedestroy($im); imagedestroy($out);
            $tr = wp_remote_post('https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId=' . rawurlencode($vid), array(
                'timeout' => 120, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'image/jpeg'),
                'body' => (string) file_get_contents($tmp),
            ));
            @unlink($tmp);
            $tc = is_wp_error($tr) ? 0 : (int) wp_remote_retrieve_response_code($tr);
            if (200 === $tc) { pd_log('Custom thumbnail gezet op YouTube.'); }
            elseif (403 === $tc) { pd_log('Custom thumbnail geweigerd (403): kanaal nog niet geverifieerd — doe dit eenmalig op youtube.com/verify.'); }
            else { pd_log('Custom thumbnail mislukt (HTTP ' . $tc . ').'); }
        }
    }
    $pl = $short ? '' : (string) (pd_get('dhs_pd_youtube_playlist') ?: pd_get('dhs_pd_youtube_playlist_id')); // Shorts niet in de afspeellijst
    if ('' !== $pl) {
        wp_remote_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('playlistId' => $pl, 'resourceId' => array('kind' => 'youtube#video', 'videoId' => $vid), 'position' => 0))))); // positie 0 = nieuwste bovenaan in de doorloop-speler
    }
    // v0.24: dag-avonturen ook in een CHRONOLOGISCHE afspeellijst (op volgorde:
    // ochtend -> middag -> avond). De feed blijft nieuwste-eerst; deze lijst is voor
    // wie een hele dag op volgorde wil kijken. We APPENDEN (geen position) = achteraan.
    if (!$short && !empty($story['arc'])) {
        $apl = (string) pd_get('pd_arc_playlist');
        if ('' === $apl) {
            $cr = wp_remote_post('https://www.googleapis.com/youtube/v3/playlists?part=snippet,status', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('title' => 'Hele dagen in het Praatdeurtjesbos', 'description' => 'Complete dagen uit het Praatdeurtjesbos, op volgorde: ochtend, middag en avond. Fijn om een hele dag achter elkaar te luisteren.'), 'status' => array('privacyStatus' => 'public')), JSON_UNESCAPED_UNICODE)));
            if (!is_wp_error($cr) && (int) wp_remote_retrieve_response_code($cr) < 300) {
                $apl = (string) (json_decode(wp_remote_retrieve_body($cr), true)['id'] ?? '');
                if ('' !== $apl) { pd_set('pd_arc_playlist', $apl); pd_log('Dag-afspeellijst aangemaakt: ' . $apl . '.'); }
            }
        }
        if ('' !== $apl) {
            wp_remote_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('playlistId' => $apl, 'resourceId' => array('kind' => 'youtube#video', 'videoId' => $vid)))))); // geen position => achteraan = chronologisch
        }
    }
    return 'youtu.be/' . $vid;
}

/* ====================================================================
 * ENGELSE VARIANT (v0.33) — zelfde beelden, Engelse tekst + stem + render
 * Ingeschakeld zodra optie pd_voice_id_en gevuld is.
 * ==================================================================== */
function pd_en_enabled(): bool {
    return '' !== (string) pd_get('pd_voice_id_en');
}

/** Vertaalt NL verhaal naar Engels via GPT-4o-mini (goedkoper dan 4o). */
function pd_translate_en(string $openai, array $story) {
    // Naam-mapping NL → EN uit de canon (optioneel veld name_en per personage).
    $names = array('Mosje' => 'Mosje', 'Kwakkel de eend' => 'Quacky the duck', 'de zingende nachtbloem' => 'the singing nightflower');
    foreach (pd_canon()['characters'] as $ch) {
        if (!empty($ch['name']) && !empty($ch['name_en'])) { $names[(string) $ch['name']] = (string) $ch['name_en']; }
    }
    $name_map = '';
    foreach ($names as $nl => $en) { $name_map .= "- {$nl} → {$en}\n"; }

    $scene_texts = array();
    foreach ((array) $story['scenes'] as $sc) { $scene_texts[] = (string) ($sc['text'] ?? ''); }

    $prompt = "Translate this Dutch children's bedtime story to warm, natural English suitable for toddlers and preschoolers (ages 1–5). Keep the gentle, cozy bedtime tone. Character name translations (use the English names):\n{$name_map}\nReturn ONLY a valid JSON object with exactly these keys:\n- title: string\n- summary: string (1–2 sentences)\n- scenes: array of exactly 5 strings (translated scene texts, same order)\n\nDutch title: " . ($story['title'] ?? '') . "\nDutch summary: " . ($story['summary'] ?? '') . "\nDutch scenes:\n" . implode("\n---\n", $scene_texts);

    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'timeout' => 60,
        'headers' => array('Authorization' => 'Bearer ' . $openai, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array('model' => 'gpt-4o-mini', 'messages' => array(array('role' => 'user', 'content' => $prompt)), 'response_format' => array('type' => 'json_object'), 'temperature' => 0.3)),
    ));
    if (is_wp_error($resp)) { return $resp; }
    if (200 !== wp_remote_retrieve_response_code($resp)) { return new WP_Error('pd_en_translate', 'OpenAI HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    $raw  = json_decode((string) ($data['choices'][0]['message']['content'] ?? '{}'), true);
    if (!is_array($raw) || empty($raw['title']) || !isset($raw['scenes'][4])) { return new WP_Error('pd_en_translate', 'Ongeldige vertaling: ' . mb_substr(wp_json_encode($raw), 0, 200, 'UTF-8')); }

    // Bouw EN scène-array: behoud alle meta-velden, vervang alleen de tekst.
    $en_scenes = array();
    foreach ((array) $story['scenes'] as $i => $sc) {
        $sc['text'] = (string) ($raw['scenes'][$i] ?? $scene_texts[$i]);
        $en_scenes[] = $sc;
    }
    return array('title' => (string) $raw['title'], 'summary' => (string) ($raw['summary'] ?? ''), 'scenes' => $en_scenes);
}

/** ElevenLabs met de Engelse stem (pd_voice_id_en). */
function pd_elevenlabs_en(string $key, array $scenes, string $stamp) {
    $voice = (string) (pd_get('pd_voice_id_en') ?: 'kLhAstPcnnPxqzk6gS5i');
    $texts = array(); foreach ($scenes as $sc) { $texts[] = trim((string) $sc['text']); }
    $joined = implode("\n\n", $texts);
    $resp = wp_remote_post('https://api.elevenlabs.io/v1/text-to-speech/' . $voice . '/with-timestamps', array(
        'timeout' => 120, 'headers' => array('xi-api-key' => $key, 'Content-Type' => 'application/json'),
        'body' => wp_json_encode(array('text' => $joined, 'model_id' => 'eleven_multilingual_v2', 'voice_settings' => array('stability' => 0.55, 'similarity_boost' => 0.8, 'style' => 0.2, 'use_speaker_boost' => true)), JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($resp)) { return $resp; }
    if (200 !== wp_remote_retrieve_response_code($resp)) { return new WP_Error('pd_tts_en_http', 'ElevenLabs EN HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['audio_base64'])) { return new WP_Error('pd_tts_en_empty', 'Geen EN audio ontvangen.'); }
    $fname = 'voice-en-' . $stamp . '.mp3';
    file_put_contents(PD_DIR . '/' . $fname, base64_decode($data['audio_base64']));
    $ends = $data['alignment']['character_end_times_seconds'] ?? array();
    $total = !empty($ends) ? (float) end($ends) : 2.0;
    $scene_end = array(); $cum = 0; $cnt = count($ends);
    foreach ($texts as $i => $t) {
        $cum += mb_strlen($t, 'UTF-8');
        $idx = max(0, min($cum - 1, $cnt - 1));
        $scene_end[$i] = !empty($ends) ? (float) $ends[$idx] : ($total * ($i + 1) / count($texts));
        $cum += 2;
    }
    $scene_end[count($texts) - 1] = $total;
    return array('url' => PD_URL_BASE . $fname, 'local' => PD_DIR . '/' . $fname, 'duration' => $total, 'scene_end' => $scene_end);
}

/** Shotstack-render met Engelse audio — zelfde beelden als NL, claim-naam 'en'. */
function pd_shotstack_submit_en(string $key, string $env, string $title, array $images, array $voice, string $stamp = '') {
    $claim = pd_shotstack_claim('en', $stamp);
    if (is_wp_error($claim)) { return $claim; }
    if (!empty($claim['render_id'])) { pd_log('Bestaande Engelse render hergebruikt: ' . $claim['render_id'] . '.'); return (string) $claim['render_id']; }
    $claim_token = (string) ($claim['token'] ?? '');
    $base = 'https://api.shotstack.io/edit/' . $env;
    $music = (string) pd_get('pd_soundtrack_url');
    if ('' === $music) { $music = 'https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/music/unminus/lit.mp3'; }
    $use_music = !in_array(strtolower($music), array('none', 'geen', '0'), true);
    $dur = (float) $voice['duration']; $total = round($dur + PD_POST, 2);
    $effects = array('zoomIn', 'slideLeft', 'zoomOut', 'slideRight', 'zoomIn');
    $img_clips = array(); $prev = 0.0; $cnt = count($images);
    foreach ($images as $i => $img) {
        $end = isset($voice['scene_end'][$i]) ? (float) $voice['scene_end'][$i] : ($dur * ($i + 1) / $cnt);
        $len = ($i === $cnt - 1) ? round($total - $prev, 2) : round($end - $prev, 2);
        if ($len < 0.5) { $len = 0.5; }
        $img_clips[] = array('asset' => array('type' => 'image', 'src' => $img['url']), 'start' => round($prev, 2), 'length' => $len, 'fit' => 'cover', 'effect' => $effects[$i % count($effects)], 'transition' => array('in' => 'fade', 'out' => 'fade'));
        $prev = $end;
    }
    $title_css = "p{font-family:'Open Sans';color:#ffffff;font-size:64px;font-weight:700;text-align:center;line-height:1.25;margin:0;text-shadow:0 2px 12px rgba(0,0,0,0.55);}";
    $title_clip = array('asset' => array('type' => 'html', 'html' => '<p>' . esc_html($title) . '</p>', 'css' => $title_css, 'width' => 1500, 'height' => 400, 'background' => 'transparent'), 'start' => 0.4, 'length' => 3.4, 'position' => 'center', 'transition' => array('in' => 'fade', 'out' => 'fade'));
    $timeline = array(
        'background' => '#fdfbf7',
        'fonts' => array(array('src' => 'https://shotstack-assets.s3-ap-southeast-2.amazonaws.com/fonts/OpenSans-Bold.ttf')),
        'tracks' => array(array('clips' => array($title_clip)), array('clips' => array(array('asset' => array('type' => 'audio', 'src' => $voice['url']), 'start' => PD_PRE, 'length' => round($dur, 2)))), array('clips' => $img_clips)),
    );
    if ($use_music) { $timeline['soundtrack'] = array('src' => $music, 'effect' => 'fadeOut', 'volume' => (float) (pd_get('pd_soundtrack_volume') ?: 0.03)); }
    $edit = array('timeline' => $timeline, 'output' => array('format' => 'mp4', 'size' => array('width' => 1920, 'height' => 1080), 'fps' => 25, 'destinations' => array(array('provider' => 'shotstack', 'exclude' => true))));
    $resp = wp_remote_post($base . '/render', array('timeout' => 60, 'headers' => array('x-api-key' => $key, 'Content-Type' => 'application/json'), 'body' => wp_json_encode($edit)));
    if (is_wp_error($resp)) { pd_shotstack_claim_release('en', $stamp, $claim_token); return $resp; }
    $status = wp_remote_retrieve_response_code($resp);
    if ($status < 200 || $status >= 300) { pd_shotstack_claim_release('en', $stamp, $claim_token); return new WP_Error('pd_en_render_http', 'Shotstack EN HTTP ' . $status . ': ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    $rid = json_decode(wp_remote_retrieve_body($resp), true)['response']['id'] ?? '';
    if ('' === $rid) { pd_shotstack_claim_release('en', $stamp, $claim_token); return new WP_Error('pd_en_render_submit', 'Geen EN render-id: ' . mb_substr(wp_remote_retrieve_body($resp), 0, 200, 'UTF-8')); }
    pd_shotstack_claim_complete('en', $stamp, $claim_token, (string) $rid);
    return (string) $rid;
}

/** YouTube-upload voor de Engelse video. Zelfde kanaal, afspeellijst "Mosje's Bedtime Stories". */
function pd_post_youtube_en(string $local, array $en_story, int $ep) {
    $refresh = (string) pd_get('dhs_pd_youtube_refresh_token');
    if ('' === $refresh) { return new WP_Error('pd_yt_cfg', 'Geen YouTube-koppeling.'); }
    $cid = (string) pd_key('dhs_youtube_client_id'); $sec = (string) pd_key('dhs_youtube_client_secret');
    if ('' === $cid || '' === $sec) { return new WP_Error('pd_yt_cfg', 'Google client ontbreekt (blog 7).'); }
    if (!file_exists($local)) { return new WP_Error('pd_yt_file', 'EN mp4 niet gevonden.'); }
    $tk = wp_remote_post('https://oauth2.googleapis.com/token', array('timeout' => 60, 'body' => array('client_id' => $cid, 'client_secret' => $sec, 'refresh_token' => $refresh, 'grant_type' => 'refresh_token')));
    if (is_wp_error($tk)) { return $tk; }
    $at = json_decode(wp_remote_retrieve_body($tk), true)['access_token'] ?? '';
    if ('' === $at) { return new WP_Error('pd_yt_token', 'Geen access-token (herautoriseren?).'); }
    $title   = (string) ($en_story['title'] ?? 'Mosje');
    $summary = (string) ($en_story['summary'] ?? '');
    $yt_title = mb_substr($title . ' 🌙 Bedtime Story for Kids | Mosje', 0, 95, 'UTF-8');
    $desc = 'A cozy bedtime story from the Little Door Forest. ' . rtrim($summary, '. ') . ". A gentle read-aloud story for bedtime.\n\n"
        . "🌙 All bedtime stories: https://www.praatdeurtje.nl/category/verhalen/\n"
        . "🚪 Who is Mosje? https://www.praatdeurtje.nl/wie-is-mosje/\n\n"
        . "Praatdeurtje makes a new, gentle bedtime story every day: softly narrated, with sweet illustrations. Perfect for toddlers and preschoolers at bedtime.\n\n"
        . "🎧 Also available as a podcast! Search for \"Mosje's Bedtime Stories\" on Spotify and Apple Podcasts.\n\n"
        . "#bedtimestory #childrenstory #readaloud #sleepstory #kidsyoutube #Mosje #Praatdeurtje #toddler #preschool";
    $snippet = array(
        'snippet' => array('title' => $yt_title, 'description' => $desc, 'tags' => array('bedtime story', 'children story', 'read aloud', 'sleep story', 'kids stories', 'gnome', 'Mosje', 'Praatdeurtje', 'calming stories', 'toddler bedtime', 'preschool bedtime', 'fairy tale', 'goodnight story', 'Little Door Forest'), 'categoryId' => '24', 'defaultLanguage' => 'en', 'defaultAudioLanguage' => 'en'),
        'status'  => array('privacyStatus' => (string) (pd_get('pd_yt_privacy') ?: 'public'), 'selfDeclaredMadeForKids' => true, 'embeddable' => true),
    );
    $size = (int) filesize($local);
    $init = wp_remote_post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', array(
        'timeout' => 60, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json; charset=UTF-8', 'X-Upload-Content-Type' => 'video/mp4', 'X-Upload-Content-Length' => (string) $size), 'body' => wp_json_encode($snippet, JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($init)) { return $init; }
    if ((int) wp_remote_retrieve_response_code($init) >= 300) { return new WP_Error('pd_yt_init', 'EN init HTTP ' . wp_remote_retrieve_response_code($init) . ': ' . mb_substr(wp_remote_retrieve_body($init), 0, 200, 'UTF-8')); }
    $upload_url = (string) wp_remote_retrieve_header($init, 'location');
    if ('' === $upload_url || !function_exists('curl_init')) { return new WP_Error('pd_yt_loc', 'geen upload-url of geen cURL'); }
    $fh = fopen($local, 'rb'); $ch = curl_init($upload_url);
    curl_setopt_array($ch, array(CURLOPT_PUT => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => $size, CURLOPT_HTTPHEADER => array('Content-Type: video/mp4'), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 600));
    $body = curl_exec($ch); $pcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fh);
    $pdata = json_decode((string) $body, true);
    if ($pcode >= 300 || empty($pdata['id'])) { return new WP_Error('pd_yt_put', 'EN upload mislukt: ' . (is_array($pdata) ? ($pdata['error']['message'] ?? ('HTTP ' . $pcode)) : ('HTTP ' . $pcode))); }
    $vid = (string) $pdata['id'];
    // Afspeellijst "Mosje's Bedtime Stories" — auto-aanmaken bij eerste video.
    $en_pl = (string) pd_get('pd_youtube_playlist_en');
    if ('' === $en_pl) {
        $cr = wp_remote_post('https://www.googleapis.com/youtube/v3/playlists?part=snippet,status', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('title' => "Mosje's Bedtime Stories", 'description' => "Gentle bedtime stories from the Little Door Forest, narrated in English. A new story every day — perfect for toddlers and preschoolers at bedtime."), 'status' => array('privacyStatus' => 'public')), JSON_UNESCAPED_UNICODE)));
        if (!is_wp_error($cr) && (int) wp_remote_retrieve_response_code($cr) < 300) {
            $en_pl = (string) (json_decode(wp_remote_retrieve_body($cr), true)['id'] ?? '');
            if ('' !== $en_pl) { pd_set('pd_youtube_playlist_en', $en_pl); pd_log('Engelse afspeellijst aangemaakt: ' . $en_pl . '.'); }
        }
    }
    if ('' !== $en_pl) {
        wp_remote_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('playlistId' => $en_pl, 'resourceId' => array('kind' => 'youtube#video', 'videoId' => $vid), 'position' => 0)))));
    }
    return 'youtu.be/' . $vid;
}

/** Aparte finalize voor de Engelse render als die nog niet klaar was bij de NL finalize. */
function pd_en_finalize(): void {
    $p = pd_get('pd_en_pending');
    if (!is_array($p) || empty($p['render_id'])) { return; }
    $shot = (string) pd_key('dhs_shotstack_api_key');
    $env  = (string) ($p['env'] ?? 'v1');
    $poll = pd_shotstack_poll($shot, $env, (string) $p['render_id']);
    if ('failed' === $poll['status']) { pd_log('Engelse render definitief mislukt.'); pd_set('pd_en_pending', ''); return; }
    if ('done' !== $poll['status']) {
        $p['attempts'] = (int) $p['attempts'] + 1;
        if ($p['attempts'] > 10) { pd_log('Engelse render-timeout — opgegeven.'); pd_set('pd_en_pending', ''); return; }
        pd_set('pd_en_pending', $p);
        wp_schedule_single_event(time() + 60, PD_EN_FINALIZE);
        return;
    }
    $ep    = (int) ($p['ep'] ?? 0);
    $stamp = (string) ($p['stamp'] ?? '');
    $en_local = PD_DIR . '/en-verhaal-' . $stamp . '.mp4';
    if (pd_download($poll['url'], $en_local)) {
        $en_yt = pd_post_youtube_en($en_local, (array) $p['story'], $ep);
        if (is_wp_error($en_yt)) { pd_log('Engelse YT mislukt (aparte taak): ' . $en_yt->get_error_message()); }
        else {
            pd_log('Engelse YT live (aparte taak): ' . $en_yt);
            $pid_int = (int) ($p['post_id'] ?? 0);
            if ($pid_int) { switch_to_blog(PD_BLOG); update_post_meta($pid_int, '_pd_youtube_en_url', (string) $en_yt); restore_current_blog(); }
            pd_podcast_en_register($ep, (array) $p['story'], $stamp, $pid_int);
        }
        @unlink($en_local); // mp4 weg; voice-en-*.mp3 blijft voor de podcast-feed
    }
    pd_set('pd_en_pending', '');
}

/* ====================================================================
 * RUSTVIDEO'S (v0.20) — wekelijkse publicatie van echte slomo-opnames
 * (deurtjes + natuur, op de laptop gemonteerd). Wachtrij: mp4-bestanden
 * in PD_DIR/rustvideos/, optioneel met gelijknamige .txt (regel 1 = titel,
 * rest = extra beschrijving). Elke zondag 18:00 NL gaat de oudste online,
 * in een eigen afspeellijst. 1 per week = de afgesproken cadans (voorraad
 * ±een jaar); Shorts/Reels zijn hergebruik en lopen niet via deze wachtrij.
 * ==================================================================== */

/** Publiceert de oudste rustvideo uit de wachtrij naar YouTube (+ eigen afspeellijst). */
function pd_rustvideo_publish(): void {
    // Eén-tegelijk-lock — zelfde bug als pd_run_daily (v0.9.0): de rustvideo-events
    // staan op élke subsite gepland, en de cron-relay bedient ze allemaal. Zonder lock
    // uploaden meerdere processen tegelijk hetzelfde bestand naar YouTube.
    // Site-transient is netwerk-breed (werkt over alle blogs).
    if (get_site_transient('pd_rust_running')) {
        pd_log('Rustvideo overgeslagen: al een run actief (lock).');
        return;
    }
    set_site_transient('pd_rust_running', 1, 30 * MINUTE_IN_SECONDS);

    $dir = PD_DIR . '/rustvideos';
    if (!is_dir($dir)) { wp_mkdir_p($dir); delete_site_transient('pd_rust_running'); return; }
    // Alleen nog-niet-gepubliceerde video's; 'klaar-*' sorteert vóór 'rustvideo-*' (k<r)
    // en zou anders bij de volgende run opnieuw geüpload worden.
    $queue = array_values(array_filter(glob($dir . '/*.mp4') ?: array(), static function ($f) {
        return strpos(basename($f), 'klaar-') !== 0;
    }));
    sort($queue);
    if (!$queue) { delete_site_transient('pd_rust_running'); return; }
    $local = $queue[0];
    $base  = basename($local, '.mp4');

    $n = (int) pd_get('pd_rust_count') + 1;
    $title = 'Een rustig momentje uit het Praatdeurtjesbos, deel ' . $n;
    $extra = '';
    $meta  = $dir . '/' . $base . '.txt';
    if (file_exists($meta)) {
        $lines = array_values(array_filter(array_map('trim', (array) file($meta))));
        if (!empty($lines[0])) { $title = $lines[0]; }
        if (count($lines) > 1) { $extra = implode("\n", array_slice($lines, 1)) . "\n\n"; }
    }
    $desc = "Een rustig, dromerig momentje uit de wereld van Praatdeurtje: echte kleine deurtjes, bloemen en bos, gefilmd in slow motion. Even samen tot rust komen voor het slapengaan.\n\n"
        . $extra
        . "Elke dag een nieuw slaapverhaaltje: https://www.praatdeurtje.nl/category/verhalen/\n"
        . "Wie is Mosje? https://www.praatdeurtje.nl/wie-is-mosje/\n\n"
        . "#rustgevend #slowmotion #natuur #kinderen #praatdeurtje";

    $result = pd_post_youtube_rust($local, mb_substr($title, 0, 95, 'UTF-8'), $desc);
    if (is_wp_error($result)) {
        pd_log('Rustvideo mislukt (' . basename($local) . '): ' . $result->get_error_message() . ' — blijft in de wachtrij.');
        delete_site_transient('pd_rust_running');
        return;
    }
    pd_set('pd_rust_count', $n);

    // Ook als Reel naar Instagram (account MyCreatief) — alleen als de koppeling er is
    // (opties mcr_instagram_user_id + mcr_instagram_token op blog 7). Mag falen zonder de rest te raken.
    $reel = pd_rustvideo_instagram(PD_URL_BASE . 'rustvideos/' . rawurlencode(basename($local)), $title);
    if (is_wp_error($reel)) { pd_log('Rustvideo-Reel overgeslagen/mislukt: ' . $reel->get_error_message()); }
    elseif ('' !== $reel) { pd_log('Rustvideo-Reel live op Instagram: ' . $reel . '.'); }

    @rename($local, $dir . '/klaar-' . gmdate('Ymd') . '-' . basename($local));
    if (file_exists($meta)) { @rename($meta, $dir . '/klaar-' . gmdate('Ymd') . '-' . basename($meta)); }
    pd_log('Rustvideo online: ' . $result . ' (' . basename($local) . '); nog ' . max(0, count($queue) - 1) . ' in de wachtrij.');
    delete_site_transient('pd_rust_running');
}

/**
 * Rustvideo als Instagram-Reel op het MyCreatief-account (Graph API, tokens op blog 7).
 * Stil overslaan als de koppeling (nog) niet bestaat.
 */
function pd_rustvideo_instagram(string $video_url, string $title) {
    $uid   = (string) pd_key('mcr_instagram_user_id');
    $token = (string) pd_key('mcr_instagram_token');
    if ('' === $uid || '' === $token) { return new WP_Error('pd_ig_off', 'geen MyCreatief IG-koppeling (mcr_instagram_user_id/token op blog 7)'); }
    $host = 'https://graph.instagram.com';
    $caption = $title . "\n\nElke zondag een nieuw rustmomentje. Meer dromerige verhaaltjes: praatdeurtje.nl\n\n#rustgevend #slowmotion #natuur #praatdeurtje #mycreatief";
    $mk = wp_remote_post($host . '/' . rawurlencode($uid) . '/media', array('timeout' => 60, 'body' => array(
        'media_type' => 'REELS', 'video_url' => $video_url, 'caption' => $caption, 'share_to_feed' => 'true',
        'thumb_offset' => '3000', // omslag op 3s — de video fadet in vanuit zwart, dus frame 0 is een zwart vlak
        'access_token' => $token,
    )));
    if (is_wp_error($mk)) { return $mk; }
    $cid = json_decode(wp_remote_retrieve_body($mk), true)['id'] ?? '';
    if ('' === $cid) { return new WP_Error('pd_ig_container', mb_substr(wp_remote_retrieve_body($mk), 0, 200, 'UTF-8')); }
    // Wachten tot Instagram de video verwerkt heeft (max ~2 min).
    for ($i = 0; $i < 12; $i++) {
        sleep(10);
        $st = wp_remote_get($host . '/' . rawurlencode($cid) . '?fields=status_code&access_token=' . rawurlencode($token), array('timeout' => 30));
        $code = is_wp_error($st) ? '' : (string) (json_decode(wp_remote_retrieve_body($st), true)['status_code'] ?? '');
        if ('FINISHED' === $code) { break; }
        if ('ERROR' === $code) { return new WP_Error('pd_ig_processing', 'Instagram kon de video niet verwerken.'); }
    }
    $pub = wp_remote_post($host . '/' . rawurlencode($uid) . '/media_publish', array('timeout' => 60, 'body' => array('creation_id' => $cid, 'access_token' => $token)));
    if (is_wp_error($pub)) { return $pub; }
    $mid = json_decode(wp_remote_retrieve_body($pub), true)['id'] ?? '';
    return '' !== $mid ? (string) $mid : new WP_Error('pd_ig_publish', mb_substr(wp_remote_retrieve_body($pub), 0, 200, 'UTF-8'));
}

/** YouTube-upload voor rustvideo's: zelfde kanaal/credentials, eigen afspeellijst (pd_rust_playlist). */
function pd_post_youtube_rust(string $local, string $title, string $desc) {
    $refresh = (string) pd_get('dhs_pd_youtube_refresh_token');
    if ('' === $refresh) { return new WP_Error('pd_yt_cfg', 'Geen YouTube-koppeling.'); }
    $cid = (string) pd_key('dhs_youtube_client_id'); $sec = (string) pd_key('dhs_youtube_client_secret');
    if ('' === $cid || '' === $sec || !file_exists($local)) { return new WP_Error('pd_yt_cfg', 'Config of bestand ontbreekt.'); }
    $tk = wp_remote_post('https://oauth2.googleapis.com/token', array('timeout' => 60, 'body' => array('client_id' => $cid, 'client_secret' => $sec, 'refresh_token' => $refresh, 'grant_type' => 'refresh_token')));
    if (is_wp_error($tk)) { return $tk; }
    $at = json_decode(wp_remote_retrieve_body($tk), true)['access_token'] ?? '';
    if ('' === $at) { return new WP_Error('pd_yt_token', 'Geen access-token.'); }
    $snippet = array(
        'snippet' => array('title' => $title, 'description' => $desc, 'tags' => array('rustgevend', 'slow motion', 'natuur', 'kabouterdeurtje', 'praatdeurtje', 'kalm', 'kinderen', 'bedtijd'), 'categoryId' => '24', 'defaultLanguage' => 'nl'),
        'status'  => array('privacyStatus' => (string) (pd_get('pd_yt_privacy') ?: 'public'), 'selfDeclaredMadeForKids' => true, 'embeddable' => true),
    );
    $size = (int) filesize($local);
    $init = wp_remote_post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', array(
        'timeout' => 60, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json; charset=UTF-8', 'X-Upload-Content-Type' => 'video/mp4', 'X-Upload-Content-Length' => (string) $size), 'body' => wp_json_encode($snippet, JSON_UNESCAPED_UNICODE),
    ));
    if (is_wp_error($init)) { return $init; }
    if ((int) wp_remote_retrieve_response_code($init) >= 300) { return new WP_Error('pd_yt_init', 'init HTTP ' . wp_remote_retrieve_response_code($init)); }
    $upload_url = (string) wp_remote_retrieve_header($init, 'location');
    if ('' === $upload_url || !function_exists('curl_init')) { return new WP_Error('pd_yt_loc', 'geen upload-url of geen cURL'); }
    $fh = fopen($local, 'rb'); $ch = curl_init($upload_url);
    curl_setopt_array($ch, array(CURLOPT_PUT => true, CURLOPT_INFILE => $fh, CURLOPT_INFILESIZE => $size, CURLOPT_HTTPHEADER => array('Content-Type: video/mp4'), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 600));
    $body = curl_exec($ch); $pcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); fclose($fh);
    $pdata = json_decode((string) $body, true);
    if ($pcode >= 300 || empty($pdata['id'])) { return new WP_Error('pd_yt_put', 'upload mislukt: ' . (is_array($pdata) ? ($pdata['error']['message'] ?? ('HTTP ' . $pcode)) : ('HTTP ' . $pcode))); }
    $vid = (string) $pdata['id'];
    // Eigen afspeellijst; bij eerste keer aanmaken en bewaren.
    $pl = (string) pd_get('pd_rust_playlist');
    if ('' === $pl) {
        $mk = wp_remote_post('https://www.googleapis.com/youtube/v3/playlists?part=snippet,status', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('title' => 'Rustige momentjes uit het Praatdeurtjesbos', 'description' => 'Echte kleine deurtjes, bloemen en bos in dromerige slow motion. Elke zondag een nieuw rustmomentje.'), 'status' => array('privacyStatus' => 'public')), JSON_UNESCAPED_UNICODE)));
        if (!is_wp_error($mk)) { $pl = (string) (json_decode(wp_remote_retrieve_body($mk), true)['id'] ?? ''); }
        if ('' !== $pl) { pd_set('pd_rust_playlist', $pl); pd_log('Rustvideo-afspeellijst aangemaakt: ' . $pl . '.'); }
    }
    if ('' !== $pl) {
        wp_remote_post('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet', array('timeout' => 40, 'headers' => array('Authorization' => 'Bearer ' . $at, 'Content-Type' => 'application/json'), 'body' => wp_json_encode(array('snippet' => array('playlistId' => $pl, 'resourceId' => array('kind' => 'youtube#video', 'videoId' => $vid), 'position' => 0)))));
    }
    return 'youtu.be/' . $vid;
}

/* ---- 6) Blogpost in categorie "Verhalen" (blog 5) ---- */
function pd_create_blog_post(array $story, array $images, string $video_url, string $yt_id, int $ep, string $thumb_url = '') {
    switch_to_blog(PD_BLOG);
    $cat = term_exists('verhalen', 'category');
    if (!$cat) { $cat = wp_insert_term('Verhalen', 'category', array('slug' => 'verhalen')); }
    $cat_id = is_array($cat) ? (int) $cat['term_id'] : (int) $cat;
    $embed = '';
    if ('' !== $yt_id) {
        $embed = '<figure class="pd-video"><iframe width="560" height="315" src="https://www.youtube-nocookie.com/embed/' . esc_attr($yt_id) . '" title="' . esc_attr($story['title']) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></figure>';
    } elseif ('' !== $video_url) {
        $embed = '<figure class="pd-video"><video controls preload="metadata" style="max-width:100%"><source src="' . esc_url($video_url) . '" type="video/mp4"></video></figure>';
    }
    $intro = '<p class="pd-intro">Een zacht slaapverhaaltje uit het Praatdeurtjesbos. Lees het zelf voor, of speel het filmpje af en luister samen.</p>';
    $body = '';
    foreach ($story['scenes'] as $i => $sc) {
        if (isset($images[$i]['url'])) { $body .= '<p><img src="' . esc_url($images[$i]['url']) . '" alt="' . esc_attr($story['title'] . ', tekening ' . ($i + 1)) . '" style="max-width:100%;height:auto;border-radius:14px" loading="lazy"></p>'; }
        $body .= '<p>' . esc_html(trim((string) $sc['text'])) . '</p>';
    }
    // Zachte CTA naar de winkel + de personagepagina (instelbaar via opties pd_cta_url / pd_mosje_url)
    $cta_url   = (string) (pd_get('pd_cta_url') ?: 'https://www.praatdeurtje.nl/winkel/');
    $mosje_url = (string) (pd_get('pd_mosje_url') ?: 'https://www.praatdeurtje.nl/wie-is-mosje/');
    $cta = '<hr style="margin:2.2em 0 1.6em;border:0;border-top:1px solid #e7e0d6">'
         . '<p class="pd-cta">🌙 Wil jij ook zo\'n deurtje als bij Mosje in huis? <a href="' . esc_url($cta_url) . '">Bekijk de handgemaakte praatdeurtjes in de winkel</a>.</p>'
         . '<p class="pd-cta-sub">Nieuw in het Praatdeurtjesbos? <a href="' . esc_url($mosje_url) . '">Lees wie Mosje en zijn vriendjes zijn</a>.</p>';
    // kses uitzetten tijdens insert: cron draait zonder gebruiker (en multisite kent
    // geen unfiltered_html), waardoor de YouTube-iframe anders wordt weggestript —
    // alle posts sinds 2026-06-03 hadden daardoor een lege <figure class="pd-video">.
    $meta = array('pd_episode' => $ep, 'pd_youtube_id' => $yt_id);
    if (is_array($story['arc'] ?? null) && !empty($story['arc']['day_id'])) { // v0.24: dag-avontuur, voor de delen-navigatie
        $meta['pd_day_id'] = (int) $story['arc']['day_id'];
        $meta['pd_part']   = (int) $story['arc']['part'];
        $meta['pd_total']  = (int) $story['arc']['total'];
    }
    kses_remove_filters();
    $post_id = wp_insert_post(array(
        'post_title' => $story['title'], 'post_status' => (string) (pd_get('pd_post_status') ?: 'publish'), 'post_type' => 'post',
        'post_category' => array($cat_id), 'post_content' => $embed . $intro . $body . $cta,
        'post_excerpt' => (string) ($story['summary'] ?? ''), 'meta_input' => $meta,
    ), true);
    kses_init_filters();
    // Uitgelichte afbeelding: een ECHTE scène uit de video (scène 1), zodat de hero op
    // de blogpost overeenkomt met wat er in de video te zien is. De aparte poster blijft
    // bestaan voor de YouTube-thumbnail; alleen als er geen scènebeelden zijn vallen we
    // terug op de poster. (v0.24.1 — hero = videoframe i.p.v. losse poster.)
    $featured = (string) ($images[0]['url'] ?? '');
    if ('' === $featured) { $featured = $thumb_url; }
    if (!is_wp_error($post_id) && $post_id && '' !== $featured) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $thumb_id = media_sideload_image($featured, $post_id, esc_attr($story['title']), 'id');
        if (!is_wp_error($thumb_id)) { set_post_thumbnail($post_id, $thumb_id); }
    }
    restore_current_blog();
    return $post_id;
}

/* v0.24: "Dit avontuur in delen"-blokje onder een dag-avontuur-post. Rendert
 * LIVE en op volgorde (ochtend -> middag -> avond) en groeit mee zodra de
 * latere delen gepubliceerd zijn — lost de nieuwste-eerst-volgorde van de feed op. */
add_filter('the_content', function ($content) {
    if (get_current_blog_id() !== PD_BLOG || !is_singular('post') || !in_the_loop() || !is_main_query()) { return $content; }
    $pid = (int) get_the_ID();
    $day = (int) get_post_meta($pid, 'pd_day_id', true);
    if ($day <= 0) { return $content; }
    $sibs = get_posts(array(
        'post_type' => 'post', 'numberposts' => 10, 'post_status' => 'publish',
        'meta_key' => 'pd_part', 'orderby' => 'meta_value_num', 'order' => 'ASC', 'fields' => 'ids',
        'meta_query' => array(array('key' => 'pd_day_id', 'value' => $day, 'type' => 'NUMERIC')),
    ));
    if (count($sibs) < 2) { return $content; }
    $labels = array(1 => 'ochtend', 2 => 'middag', 3 => 'avond');
    $li = '';
    foreach ($sibs as $sid) {
        $pp  = (int) get_post_meta($sid, 'pd_part', true);
        $lab = 'Deel ' . $pp . (isset($labels[$pp]) ? ' (' . $labels[$pp] . ')' : '');
        if ((int) $sid === $pid) { $li .= '<li><strong>' . esc_html($lab) . ' — je kijkt nu dit deel</strong></li>'; }
        else { $li .= '<li><a href="' . esc_url(get_permalink($sid)) . '">' . esc_html($lab) . '</a></li>'; }
    }
    $nav = '<aside class="pd-reeks" style="margin:2em 0;padding:14px 18px;background:#f7f4ee;border:1px solid #e7e0d6;border-radius:14px">'
         . '<p style="margin:0 0 6px;font-weight:600">📖 Dit avontuur speelt zich af over één dag, in delen:</p>'
         . '<ol style="margin:0;padding-left:1.3em">' . $li . '</ol></aside>';
    return $content . $nav;
}, 20);

/* Spotify-badge + "Watch in English" blok onder elke verhalenblogpost (v0.33/0.34). */
add_filter('the_content', function ($content) {
    if (get_current_blog_id() !== PD_BLOG || !is_singular('post') || !in_the_loop() || !is_main_query()) { return $content; }
    $extra = '';

    // Spotify-badge — toont als pd_spotify_url gezet is (NL of EN show).
    $sp_url = (string) pd_get('pd_spotify_url');
    if ('' !== $sp_url) {
        $extra .= '<div class="pd-spotify-link" style="margin:1.5em 0 0;padding:1em 1.4em;background:#f0faf4;border-radius:12px;border-left:4px solid #1DB954;display:flex;align-items:center;gap:0.9em">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#1DB954" style="flex-shrink:0"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.515 17.321a.75.75 0 0 1-1.031.249c-2.825-1.726-6.38-2.116-10.567-1.16a.75.75 0 1 1-.334-1.462c4.584-1.047 8.52-.596 11.683 1.342a.75.75 0 0 1 .249 1.031zm1.471-3.27a.937.937 0 0 1-1.288.308c-3.232-1.987-8.158-2.563-11.982-1.403a.938.938 0 0 1-.543-1.794c4.368-1.323 9.796-.682 13.505 1.601a.937.937 0 0 1 .308 1.288zm.127-3.407C15.28 8.55 9.108 8.35 5.55 9.418a1.125 1.125 0 1 1-.652-2.154c4.107-1.245 10.934-1.004 15.248 1.62a1.125 1.125 0 0 1-1.033 2.01z"/></svg>'
                . '<span><strong style="display:block;margin-bottom:2px">Luister op Spotify</strong>'
                . '<a href="' . esc_url($sp_url) . '" target="_blank" rel="noopener" style="color:#1a7a40;text-decoration:none">Mosje\'s Bedtime Stories — open in Spotify &#x2192;</a></span>'
                . '</div>';
    }

    // "Watch in English" YouTube-blok.
    $en_url = (string) get_post_meta((int) get_the_ID(), '_pd_youtube_en_url', true);
    if ('' !== $en_url) {
        $vid_id = ltrim(str_replace('youtu.be/', '', $en_url), '/');
        $extra .= '<div class="pd-en-link" style="margin:1em 0 0;padding:1.1em 1.4em;background:#eef7f2;border-radius:12px;border-left:4px solid #5aaa80;display:flex;align-items:center;gap:0.9em">'
                . '<span style="font-size:1.5em;flex-shrink:0">🌍</span>'
                . '<span><strong style="display:block;margin-bottom:2px">Also available in English</strong>'
                . '<a href="https://youtu.be/' . esc_attr($vid_id) . '" target="_blank" rel="noopener" style="color:#2a7a55;text-decoration:none">Watch this story in English on YouTube &#x2192;</a></span>'
                . '</div>';
    }

    return '' !== $extra ? $content . $extra : $content;
}, 25);

/* Banner boven de verhalenlijst — Engelse YT-afspeellijst + Spotify (v0.33/0.34). */
add_action('loop_start', function ($query) {
    if (get_current_blog_id() !== PD_BLOG || !$query->is_main_query() || !is_category('verhalen')) { return; }
    $en_pl  = (string) pd_get('pd_youtube_playlist_en');
    $sp_url = (string) pd_get('pd_spotify_url');
    if ('' === $en_pl && '' === $sp_url) { return; }
    $links = '';
    if ('' !== $en_pl) {
        $pl_url = 'https://www.youtube.com/playlist?list=' . rawurlencode($en_pl);
        $links .= '<a href="' . esc_url($pl_url) . '" target="_blank" rel="noopener" style="color:#2a7a55;white-space:nowrap">&#x25B6; Watch on YouTube</a>';
    }
    if ('' !== $sp_url) {
        if ('' !== $links) { $links .= ' &nbsp;·&nbsp; '; }
        $links .= '<a href="' . esc_url($sp_url) . '" target="_blank" rel="noopener" style="color:#1a7a40;white-space:nowrap">&#127925; Listen on Spotify</a>';
    }
    echo '<div class="pd-en-banner" style="margin:0 0 2em;padding:1.1em 1.5em;background:#eef7f2;border-radius:14px;border-left:4px solid #5aaa80;display:flex;align-items:center;gap:1em">'
       . '<span style="font-size:1.6em;flex-shrink:0">🌍</span>'
       . '<span><strong>Also available in English!</strong> Mosje\'s bedtime stories are now narrated in English too. '
       . $links . '</span>'
       . '</div>';
});

/* ====================================================================
 * 7) PODCAST — de voorlees-mp3 blijft bewaard; ?pd_podcast=1 geeft een
 * RSS-feed die je gratis aanmeldt bij Spotify (for Creators) / Apple.
 * ==================================================================== */
function pd_podcast_items(): array {
    $raw = pd_get('pd_podcast_items', '');
    $items = is_array($raw) ? $raw : json_decode((string) $raw, true);
    return is_array($items) ? $items : array();
}

function pd_podcast_register(int $ep, array $story, array $p, int $post_id): void {
    $local = (string) ($p['voice_local'] ?? '');
    if ('' === $local || !file_exists($local)) { return; }
    $items = pd_podcast_items();
    foreach ($items as $it) { if ((int) ($it['ep'] ?? 0) === $ep) { return; } } // niet dubbel
    array_unshift($items, array(
        'ep'       => $ep,
        'title'    => (string) $story['title'],
        'summary'  => (string) ($story['summary'] ?? ''),
        'mp3'      => PD_URL_BASE . basename($local),
        'bytes'    => (int) (@filesize($local) ?: 0),
        'duration' => (int) round((float) ($p['voice_duration'] ?? 0)),
        'date'     => gmdate('D, d M Y H:i:s') . ' +0000',
        'post_id'  => $post_id,
    ));
    pd_set('pd_podcast_items', wp_json_encode(array_slice($items, 0, 400), JSON_UNESCAPED_UNICODE));
    pd_log('Podcast-aflevering geregistreerd: ep ' . $ep . '.');
}

function pd_podcast_feed(): void {
    if (function_exists('nocache_headers')) { nocache_headers(); }
    if (function_exists('wp_cache_set_no_cache_flag')) { wp_cache_set_no_cache_flag(true); }
    if (defined('DONOTCACHEPAGE') === false) { define('DONOTCACHEPAGE', true); }
    $items = pd_podcast_items();
    $site  = 'https://www.praatdeurtje.nl';
    $cover = PD_URL_BASE . 'podcast-cover.png';
    $desc  = 'Elke dag een zacht slaapverhaaltje uit het Praatdeurtjesbos, met Mosje het kaboutertje en zijn vriendjes. Rustig voorgelezen, fijn voor peuters en kleuters bij het slapengaan.';
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    header('Content-Type: application/rss+xml; charset=utf-8');
    $out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $out .= '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n<channel>\n";
    $out .= '<title>Praatdeurtje slaapverhaaltjes</title>' . "\n";
    $out .= '<link>' . $e($site) . '</link>' . "\n";
    $out .= '<atom:link href="' . $e($site . '/?pd_podcast=1') . '" rel="self" type="application/rss+xml" />' . "\n";
    $out .= '<language>nl</language>' . "\n";
    $out .= '<description>' . $e($desc) . '</description>' . "\n";
    $out .= '<itunes:author>Praatdeurtje</itunes:author>' . "\n";
    $out .= '<itunes:summary>' . $e($desc) . '</itunes:summary>' . "\n";
    $email = (string) (pd_get('pd_podcast_email') ?: 'myklijn@gmail.com');
    $out .= '<itunes:owner><itunes:name>Praatdeurtje</itunes:name><itunes:email>' . $e($email) . '</itunes:email></itunes:owner>' . "\n";
    $out .= '<managingEditor>' . $e($email) . ' (Praatdeurtje)</managingEditor>' . "\n";
    $out .= '<itunes:image href="' . $e($cover) . '" />' . "\n";
    $out .= '<itunes:category text="Kids &amp; Family"><itunes:category text="Stories for Kids" /></itunes:category>' . "\n";
    $out .= '<itunes:explicit>false</itunes:explicit>' . "\n";
    foreach ($items as $it) {
        $link = ((int) ($it['post_id'] ?? 0) > 0) ? get_blog_permalink(PD_BLOG, (int) $it['post_id']) : $site;
        if (!$link) { $link = $site; }
        $out .= "<item>\n";
        $out .= '<title>' . $e($it['title'] ?? '') . '</title>' . "\n";
        $out .= '<description>' . $e($it['summary'] ?? '') . '</description>' . "\n";
        $out .= '<link>' . $e($link) . '</link>' . "\n";
        $out .= '<guid isPermaLink="false">' . $e($it['mp3'] ?? '') . '</guid>' . "\n";
        $out .= '<pubDate>' . $e($it['date'] ?? '') . '</pubDate>' . "\n";
        $out .= '<enclosure url="' . $e($it['mp3'] ?? '') . '" length="' . (int) ($it['bytes'] ?? 0) . '" type="audio/mpeg" />' . "\n";
        if (!empty($it['duration'])) { $out .= '<itunes:duration>' . (int) $it['duration'] . '</itunes:duration>' . "\n"; }
        $out .= '<itunes:explicit>false</itunes:explicit>' . "\n";
        $out .= "</item>\n";
    }
    $out .= "</channel>\n</rss>\n";
    echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ====================================================================
 * 7b) ENGELSE PODCAST — ?pd_podcast_en=1 (RSS voor Spotify / Apple Podcasts).
 * De Engelse mp3 (voice-en-*.mp3) blijft op de server na de YouTube-upload.
 * Aanmelden: Spotify for Creators → https://podcasters.spotify.com/ → RSS-feed.
 * ==================================================================== */
function pd_podcast_en_items(): array {
    $raw = pd_get('pd_podcast_en_items', '');
    $items = is_array($raw) ? $raw : json_decode((string) $raw, true);
    return is_array($items) ? $items : array();
}

function pd_podcast_en_register(int $ep, array $en_story, string $stamp, int $post_id): void {
    if ('' === $stamp) { return; }
    $local = PD_DIR . '/voice-en-' . $stamp . '.mp3';
    if (!file_exists($local)) { pd_log('EN podcast-mp3 niet gevonden voor ep ' . $ep . ' (stamp ' . $stamp . ') — overgeslagen.'); return; }
    $items = pd_podcast_en_items();
    foreach ($items as $it) { if ((int) ($it['ep'] ?? 0) === $ep) { return; } } // niet dubbel
    array_unshift($items, array(
        'ep'       => $ep,
        'title'    => (string) ($en_story['title'] ?? 'Mosje ep ' . $ep),
        'summary'  => (string) ($en_story['summary'] ?? ''),
        'mp3'      => PD_URL_BASE . 'voice-en-' . $stamp . '.mp3',
        'bytes'    => (int) (@filesize($local) ?: 0),
        'duration' => (int) round((float) ($en_story['duration'] ?? 0)),
        'date'     => gmdate('D, d M Y H:i:s') . ' +0000',
        'post_id'  => $post_id,
        'stamp'    => $stamp,
    ));
    pd_set('pd_podcast_en_items', wp_json_encode(array_slice($items, 0, 400), JSON_UNESCAPED_UNICODE));
    pd_log('Engelse podcast-aflevering geregistreerd: ep ' . $ep . '.');
}

function pd_podcast_en_feed(): void {
    if (function_exists('nocache_headers')) { nocache_headers(); }
    if (function_exists('wp_cache_set_no_cache_flag')) { wp_cache_set_no_cache_flag(true); }
    if (defined('DONOTCACHEPAGE') === false) { define('DONOTCACHEPAGE', true); }
    $items = pd_podcast_en_items();
    $site  = 'https://www.praatdeurtje.nl';
    $cover = PD_URL_BASE . 'podcast-cover-en.png';
    if (!file_exists(PD_DIR . '/podcast-cover-en.png')) { $cover = PD_URL_BASE . 'podcast-cover.png'; }
    $desc  = "A new gentle bedtime story from the Little Door Forest every day, narrated in English. Join Mosje the little gnome and his friends on cozy adventures. Perfect for toddlers and preschoolers at bedtime.";
    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    header('Content-Type: application/rss+xml; charset=utf-8');
    $out  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $out .= '<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n<channel>\n";
    $out .= '<title>Mosje\'s Bedtime Stories</title>' . "\n";
    $out .= '<link>' . $e($site) . '</link>' . "\n";
    $out .= '<atom:link href="' . $e($site . '/?pd_podcast_en=1') . '" rel="self" type="application/rss+xml" />' . "\n";
    $out .= '<language>en</language>' . "\n";
    $out .= '<description>' . $e($desc) . '</description>' . "\n";
    $out .= '<itunes:author>Praatdeurtje</itunes:author>' . "\n";
    $out .= '<itunes:summary>' . $e($desc) . '</itunes:summary>' . "\n";
    $email = (string) (pd_get('pd_podcast_email') ?: 'myklijn@gmail.com');
    $out .= '<itunes:owner><itunes:name>Praatdeurtje</itunes:name><itunes:email>' . $e($email) . '</itunes:email></itunes:owner>' . "\n";
    $out .= '<managingEditor>' . $e($email) . ' (Praatdeurtje)</managingEditor>' . "\n";
    $out .= '<itunes:image href="' . $e($cover) . '" />' . "\n";
    $out .= '<itunes:category text="Kids &amp; Family"><itunes:category text="Stories for Kids" /></itunes:category>' . "\n";
    $out .= '<itunes:explicit>false</itunes:explicit>' . "\n";
    foreach ($items as $it) {
        $link = ((int) ($it['post_id'] ?? 0) > 0) ? get_blog_permalink(PD_BLOG, (int) $it['post_id']) : $site;
        if (!$link) { $link = $site; }
        $out .= "<item>\n";
        $out .= '<title>' . $e($it['title'] ?? '') . '</title>' . "\n";
        $out .= '<description>' . $e($it['summary'] ?? '') . '</description>' . "\n";
        $out .= '<link>' . $e($link) . '</link>' . "\n";
        $out .= '<guid isPermaLink="false">' . $e($it['mp3'] ?? '') . '</guid>' . "\n";
        $out .= '<pubDate>' . $e($it['date'] ?? '') . '</pubDate>' . "\n";
        $out .= '<enclosure url="' . $e($it['mp3'] ?? '') . '" length="' . (int) ($it['bytes'] ?? 0) . '" type="audio/mpeg" />' . "\n";
        if (!empty($it['duration'])) { $out .= '<itunes:duration>' . (int) $it['duration'] . '</itunes:duration>' . "\n"; }
        $out .= '<itunes:explicit>false</itunes:explicit>' . "\n";
        $out .= "</item>\n";
    }
    $out .= "</channel>\n</rss>\n";
    echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* ====================================================================
 * 8) KLEURPLAAT — lijntekening van scène 1, onderaan de blogpost.
 * ==================================================================== */
function pd_make_coloring($job) {
    @set_time_limit(280);
    if (!is_array($job) || empty($job['post_id']) || empty($job['src'])) { return array('skipped' => 'ongeldige job'); }
    $key = (string) pd_get('pd_openai_api_key');
    $src = (string) $job['src'];
    if ('' === $key || !file_exists($src)) { return array('skipped' => 'geen key of bronbeeld'); }
    $prompt = 'Convert the reference illustration into a printable colouring page for young children (age 2 to 6): clean smooth BLACK outlines only, on a pure WHITE background. No shading, no grey tones, no colour fill anywhere. Simplified friendly shapes with thick clear lines that are easy to colour inside, keep the same characters and scene clearly recognisable. No text anywhere in the image.';
    $b64 = pd_openai_image($key, $prompt, array($src));
    if (is_wp_error($b64)) { pd_log('Kleurplaat mislukt: ' . $b64->get_error_message()); return array('error' => $b64->get_error_message()); }
    $file = PD_DIR . '/kleurplaat-' . (string) $job['stamp'] . '.png';
    file_put_contents($file, base64_decode((string) $b64));
    $url = PD_URL_BASE . basename($file);
    switch_to_blog(PD_BLOG);
    $post = get_post((int) $job['post_id']);
    if ($post && false === strpos($post->post_content, 'pd-kleurplaat')) {
        $blok = '<div class="pd-kleurplaat"><h3>🖍️ Kleurplaat van dit verhaaltje</h3>'
              . '<p>Print de kleurplaat en kleur de tekening zelf in. Veel plezier!</p>'
              . '<p><a href="' . esc_url($url) . '" download><img src="' . esc_url($url) . '" alt="' . esc_attr('Kleurplaat: ' . (string) ($job['title'] ?? '')) . '" style="max-width:100%;height:auto;border-radius:14px;border:1px solid #eee" loading="lazy"></a></p>'
              . '<p><a href="' . esc_url($url) . '" download>👉 Download de kleurplaat</a></p></div>';
        kses_remove_filters(); // anders strippen content-filters attributen (cron = geen gebruiker)
        wp_update_post(array('ID' => $post->ID, 'post_content' => $post->post_content . $blok));
        kses_init_filters();
        pd_log('Kleurplaat toegevoegd aan post ' . $post->ID . '.');
        // v0.28.0: kleurplaat ook als losse foto naar Facebook
        wp_schedule_single_event(time() + 30, PD_FB_COLORING, array(array('post_id' => (int) $post->ID, 'url' => $url, 'title' => (string) ($job['title'] ?? ''))));
    }
    restore_current_blog();
    return array('done' => basename($file));
}

// ============================================================================
// v0.28.0 — Facebook publishing (Praatdeurtje-pagina)
// Cadens: per aflevering 1x link-post (blog + YT-link), 1x foto-post (kleurplaat).
// Tokens via wp-admin: Instellingen -> Praatdeurtje FB. Page Token is permanent.
// ============================================================================
const PD_FB_STORY    = 'pd_fb_story_event';
const PD_FB_COLORING = 'pd_fb_coloring_event';
add_action(PD_FB_STORY,    'pd_fb_publish_story',    10, 1);
add_action(PD_FB_COLORING, 'pd_fb_publish_coloring', 10, 1);

function pd_fb_call($path, array $params, $method = 'POST') {
    switch_to_blog(PD_BLOG);
    $page_id = (string) pd_get('pd_fb_page_id');
    $token   = (string) pd_get('pd_fb_page_token');
    restore_current_blog();
    if ('' === $page_id || '' === $token) { return new WP_Error('pd_fb_no_token', 'pd_fb_page_id of pd_fb_page_token niet ingesteld'); }
    $params['access_token'] = $token;
    $url = 'https://graph.facebook.com/v21.0/' . ltrim(str_replace('{page}', $page_id, $path), '/');
    $args = array('timeout' => 30);
    if ('POST' === $method) { $args['body'] = $params; $resp = wp_remote_post($url, $args); }
    else { $url = add_query_arg($params, $url); $resp = wp_remote_get($url, $args); }
    if (is_wp_error($resp)) { return $resp; }
    $code = (int) wp_remote_retrieve_response_code($resp);
    $body = json_decode((string) wp_remote_retrieve_body($resp), true);
    if ($code >= 200 && $code < 300) { return is_array($body) ? $body : array(); }
    return new WP_Error('pd_fb_err', 'FB API ' . $code . ': ' . wp_json_encode($body));
}

function pd_fb_publish_story($args) {
    $post_id = (int) ($args['post_id'] ?? 0);
    $yt      = (string) ($args['yt'] ?? '');
    if ($post_id <= 0) { return; }
    switch_to_blog(PD_BLOG);
    if (get_post_meta($post_id, '_pd_fb_posted', true)) { restore_current_blog(); return; }
    $post = get_post($post_id);
    if (!$post) { restore_current_blog(); return; }
    $url   = get_permalink($post_id);
    $title = get_the_title($post_id);
    $exc   = wp_strip_all_tags((string) $post->post_excerpt);
    if ('' === $exc) { $exc = wp_trim_words(wp_strip_all_tags($post->post_content), 30); }
    $msg = $title . "\n\n" . $exc;
    if ('' !== $yt) { $msg .= "\n\nBekijk de video: " . $yt; }
    $msg .= "\n\nLees het verhaaltje: " . $url;
    restore_current_blog();
    $r = pd_fb_call('{page}/feed', array('message' => $msg, 'link' => $url));
    if (is_wp_error($r)) { pd_log('FB-post mislukt: ' . $r->get_error_message()); return; }
    switch_to_blog(PD_BLOG);
    update_post_meta($post_id, '_pd_fb_posted', (string) ($r['id'] ?? '1'));
    restore_current_blog();
    pd_log('FB-post live: ' . (string) ($r['id'] ?? '?'));
}

function pd_fb_publish_coloring($args) {
    $post_id = (int) ($args['post_id'] ?? 0);
    $img_url = (string) ($args['url'] ?? '');
    $title   = (string) ($args['title'] ?? '');
    if ($post_id <= 0 || '' === $img_url) { return; }
    switch_to_blog(PD_BLOG);
    if (get_post_meta($post_id, '_pd_fb_coloring_posted', true)) { restore_current_blog(); return; }
    $url = get_permalink($post_id);
    if ('' === $title) { $title = get_the_title($post_id); }
    restore_current_blog();
    $cap = 'Kleurplaat bij "' . $title . '". Print de tekening en kleur hem zelf in.' . "\n\nHet verhaaltje: " . $url;
    $r = pd_fb_call('{page}/photos', array('url' => $img_url, 'caption' => $cap));
    if (is_wp_error($r)) { pd_log('FB-kleurplaat mislukt: ' . $r->get_error_message()); return; }
    switch_to_blog(PD_BLOG);
    update_post_meta($post_id, '_pd_fb_coloring_posted', (string) ($r['post_id'] ?? $r['id'] ?? '1'));
    restore_current_blog();
    pd_log('FB-kleurplaat live: ' . (string) ($r['post_id'] ?? $r['id'] ?? '?'));
}

// Admin-pagina: FB-koppeling (alleen op blog 5)
add_action('admin_menu', function () {
    if (get_current_blog_id() !== PD_BLOG) { return; }
    add_submenu_page('options-general.php', 'Praatdeurtje FB', 'Praatdeurtje FB', 'manage_options', 'pd-fb', 'pd_fb_admin_page');
});
function pd_fb_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    if (!empty($_POST['pd_fb_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['pd_fb_nonce'])), 'pd_fb_save')) {
        pd_set('pd_fb_page_id', sanitize_text_field((string) ($_POST['pd_fb_page_id'] ?? '')));
        pd_set('pd_fb_page_token', sanitize_text_field((string) ($_POST['pd_fb_page_token'] ?? '')));
        echo '<div class="notice notice-success"><p>Opgeslagen.</p></div>';
    }
    if (isset($_GET['pd_fb_test']) && check_admin_referer('pd_fb_test')) {
        $r = pd_fb_call('{page}?fields=id,name,fan_count', array(), 'GET');
        if (is_wp_error($r)) { echo '<div class="notice notice-error"><p>Test mislukt: ' . esc_html($r->get_error_message()) . '</p></div>'; }
        else { echo '<div class="notice notice-success"><p>Verbonden met pagina: <strong>' . esc_html((string) ($r['name'] ?? '?')) . '</strong> (id ' . esc_html((string) ($r['id'] ?? '?')) . ', ' . (int) ($r['fan_count'] ?? 0) . ' volgers).</p></div>'; }
    }
    if (isset($_GET['pd_fb_backfill']) && check_admin_referer('pd_fb_backfill')) {
        switch_to_blog(PD_BLOG);
        $cat = get_category_by_slug('verhalen');
        $q = new WP_Query(array(
            'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1,
            'orderby' => 'date', 'order' => 'ASC',
            'cat' => $cat ? (int) $cat->term_id : 0,
            'meta_query' => array(array('key' => '_pd_fb_posted', 'compare' => 'NOT EXISTS')),
        ));
        $ids = wp_list_pluck($q->posts, 'ID');
        restore_current_blog();
        $offset = 60;
        foreach ($ids as $pid) {
            wp_schedule_single_event(time() + $offset, PD_FB_STORY, array(array('post_id' => (int) $pid, 'yt' => (string) get_post_meta($pid, '_pd_youtube_url', true))));
            $offset += 90; // 90s tussen elke post
        }
        echo '<div class="notice notice-success"><p>Backfill ingepland: <strong>' . count($ids) . '</strong> verhalen (1 per 90s, eerste over 60s).</p></div>';
    }
    $pid = esc_attr((string) pd_get('pd_fb_page_id'));
    $tok = esc_attr((string) pd_get('pd_fb_page_token'));
    echo '<div class="wrap"><h1>Praatdeurtje — Facebook-koppeling</h1>';
    echo '<p>Per aflevering wordt automatisch een link-post (blog + YouTube) en een losse foto-post (kleurplaat) geplaatst op de Praatdeurtje-FB-pagina.</p>';
    echo '<form method="post"><table class="form-table">';
    echo '<tr><th>Page ID</th><td><input type="text" name="pd_fb_page_id" value="' . $pid . '" class="regular-text"></td></tr>';
    echo '<tr><th>Page Access Token</th><td><input type="password" name="pd_fb_page_token" value="' . $tok . '" class="large-text"><p class="description">Long-lived Page Token (verloopt niet).</p></td></tr>';
    echo '</table>';
    wp_nonce_field('pd_fb_save', 'pd_fb_nonce');
    submit_button('Opslaan');
    echo '</form>';
    $test_url = wp_nonce_url(add_query_arg('pd_fb_test', '1'), 'pd_fb_test');
    $bf_url   = wp_nonce_url(add_query_arg('pd_fb_backfill', '1'), 'pd_fb_backfill');
    echo '<p><a class="button" href="' . esc_url($test_url) . '">Test verbinding</a> ';
    echo '<a class="button" href="' . esc_url($bf_url) . '" onclick="return confirm(\'Alle nog-niet-geposte verhalen naar Facebook duwen (1 per 90 seconden)?\')">Backfill: post oude verhalen</a></p>';
    echo '<p class="description">Backfill plant alle al-gepubliceerde verhalen die nog geen FB-post hebben, met 90 seconden tussen elk. Veilig om vaker te draaien: posts met _pd_fb_posted-meta worden overgeslagen.</p>';
    echo '</div>';
}

/* ====================================================================
 * ADMINPAGINA "Engels (EN)" — stem, afspeellijst en naam-vertalingen
 * ==================================================================== */
function pd_admin_english_page(): void {
    if (!current_user_can('manage_options')) { return; }
    if (isset($_POST['pd_en_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['pd_en_nonce'])), 'pd_en_save')) {
        pd_set('pd_voice_id_en',       sanitize_text_field(wp_unslash((string) ($_POST['pd_voice_id_en'] ?? ''))));
        pd_set('pd_youtube_playlist_en', sanitize_text_field(wp_unslash((string) ($_POST['pd_youtube_playlist_en'] ?? ''))));
        // Naam-EN per personage opslaan in de canon
        $canon = pd_canon();
        $names_en = (array) ($_POST['pd_char_name_en'] ?? array());
        foreach ($canon['characters'] as $i => $ch) {
            $slug = pd_slugify((string) ($ch['name'] ?? ''));
            if (isset($names_en[$slug])) { $canon['characters'][$i]['name_en'] = sanitize_text_field(wp_unslash((string) $names_en[$slug])); }
        }
        pd_canon_save($canon);
        echo '<div class="notice notice-success"><p>Instellingen opgeslagen.</p></div>';
    }
    $voice_en = esc_attr((string) pd_get('pd_voice_id_en'));
    $pl_en    = esc_attr((string) pd_get('pd_youtube_playlist_en'));
    $canon    = pd_canon();
    echo '<div class="wrap"><h1>Praatdeurtje — Engels (EN)</h1>';
    echo '<p>Elke aflevering krijgt automatisch een Engelse variant op YouTube (zelfde illustraties, Engelse vertaling + stem). Laat het voice-veld leeg om de Engelse variant uit te zetten.</p>';
    echo '<form method="post"><table class="form-table">';
    echo '<tr><th>ElevenLabs voice-ID (EN)</th><td><input type="text" name="pd_voice_id_en" value="' . $voice_en . '" class="regular-text"><p class="description">Standaard: kLhAstPcnnPxqzk6gS5i. Leeg = Engelse variant uitgeschakeld.</p></td></tr>';
    echo '<tr><th>YouTube afspeellijst-ID (EN)</th><td><input type="text" name="pd_youtube_playlist_en" value="' . $pl_en . '" class="regular-text"><p class="description">Wordt automatisch aangemaakt ("Mosje\'s Bedtime Stories") bij de eerste video. Je kunt een bestaand ID invullen om videos daarin te zetten.</p></td></tr>';
    echo '</table>';
    echo '<h2>Engelse karakternamen</h2><p>Optioneel: vul een Engelse naam in per personage. Wordt gebruikt in de vertaalprompt.</p>';
    echo '<table class="form-table">';
    foreach ($canon['characters'] as $ch) {
        $slug = pd_slugify((string) ($ch['name'] ?? ''));
        $en_val = esc_attr((string) ($ch['name_en'] ?? ''));
        echo '<tr><th>' . esc_html((string) ($ch['name'] ?? '')) . '</th><td><input type="text" name="pd_char_name_en[' . esc_attr($slug) . ']" value="' . $en_val . '" class="regular-text" placeholder="Engelse naam (optioneel)"></td></tr>';
    }
    echo '</table>';
    wp_nonce_field('pd_en_save', 'pd_en_nonce');
    submit_button('Opslaan');
    echo '</form>';
    if ('' !== $voice_en) {
        echo '<hr><h2>Status</h2><p>Engelse variant: <strong style="color:green">actief</strong> (voice ' . esc_html($voice_en) . ').</p>';
    } else {
        echo '<hr><h2>Status</h2><p>Engelse variant: <strong style="color:#666">uitgeschakeld</strong> (geen voice-ID ingesteld).</p>';
    }
    echo '</div>';
}

/* ====================================================================
 * TIKTOK-PUBLISHER — standalone (no dierenhart-core class dependency)
 * Credentials opgeslagen op blog 5 (pd_-prefix).
 * ==================================================================== */

function pd_tt_access_token(): string|\WP_Error {
    $token = trim((string) pd_get('pd_tiktok_token'));
    if ('' !== $token) { return $token; }

    $cached = get_blog_option(PD_BLOG, '_pd_tt_access_token_cache', '');
    $cached_exp = (int) get_blog_option(PD_BLOG, '_pd_tt_access_token_exp', 0);
    if ('' !== $cached && time() < $cached_exp) { return $cached; }

    $client_key    = trim((string) pd_get('pd_tiktok_client_key'));
    $client_secret = trim((string) pd_get('pd_tiktok_client_secret'));
    $refresh       = trim((string) pd_get('pd_tiktok_refresh_token'));
    if ('' === $client_key || '' === $client_secret || '' === $refresh) {
        return new WP_Error('pd_tt_no_creds', 'TikTok-credentials ontbreken (praatdeurtje).');
    }
    $resp = wp_remote_post('https://open.tiktokapis.com/v2/oauth/token/', array(
        'timeout' => 30,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
        'body'    => array(
            'client_key'    => $client_key,
            'client_secret' => $client_secret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh,
        ),
    ));
    if (is_wp_error($resp)) { return new WP_Error('pd_tt_http', 'TikTok token HTTP: ' . $resp->get_error_message()); }
    $d = json_decode((string) wp_remote_retrieve_body($resp), true);
    $access = is_array($d) ? (string) ($d['access_token'] ?? '') : '';
    if ('' === $access) { return new WP_Error('pd_tt_no_token', 'Geen TikTok access-token (refresh verlopen?).'); }
    $ttl = max(60, (int) ($d['expires_in'] ?? 86400) - 120);
    update_blog_option(PD_BLOG, '_pd_tt_access_token_cache', $access);
    update_blog_option(PD_BLOG, '_pd_tt_access_token_exp', time() + $ttl);
    if (!empty($d['refresh_token']) && (string) $d['refresh_token'] !== $refresh) {
        pd_set('pd_tiktok_refresh_token', (string) $d['refresh_token']);
    }
    return $access;
}

/** Stuurt de video naar TikTok via PULL_FROM_URL. Geeft TikTok-profiel-URL terug of WP_Error. */
function pd_post_tiktok(string $video_url, array $story): string|\WP_Error {
    if ('1' !== (string) pd_get('pd_tiktok_enabled')) {
        return new WP_Error('pd_tt_disabled', 'TikTok uitgeschakeld.');
    }
    if ('' === $video_url) { return new WP_Error('pd_tt_no_video', 'Geen video-URL voor TikTok.'); }

    $token = pd_tt_access_token();
    if (is_wp_error($token)) { return $token; }

    $privacy = (string) pd_get('pd_tiktok_privacy');
    if (!in_array($privacy, array('SELF_ONLY', 'PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR'), true)) {
        $privacy = 'SELF_ONLY';
    }

    $caption = mb_substr((string) ($story['title'] ?? ''), 0, 100, 'UTF-8');
    $caption .= ' #praatdeurtje #kinderverhaaltje #bedtimestory #mosje';

    $resp = wp_remote_post('https://open.tiktokapis.com/v2/post/publish/video/init/', array(
        'timeout' => 60,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json; charset=UTF-8',
        ),
        'body' => wp_json_encode(array(
            'post_info'   => array(
                'title'                    => $caption,
                'privacy_level'            => $privacy,
                'disable_comment'          => false,
                'disable_duet'             => false,
                'disable_stitch'           => false,
                'video_cover_timestamp_ms' => 1000,
            ),
            'source_info' => array(
                'source'    => 'PULL_FROM_URL',
                'video_url' => $video_url,
            ),
        )),
    ));

    if (is_wp_error($resp)) { return new WP_Error('pd_tt_http', 'TikTok HTTP: ' . $resp->get_error_message()); }
    $data = json_decode((string) wp_remote_retrieve_body($resp), true);
    $err  = is_array($data) ? ($data['error'] ?? array()) : array();
    if (is_array($err) && isset($err['code']) && 'ok' !== $err['code']) {
        return new WP_Error('pd_tt_api', 'TikTok API: ' . (string) ($err['message'] ?? $err['code']));
    }
    $publish_id = is_array($data) ? (string) ($data['data']['publish_id'] ?? '') : '';
    if ('' === $publish_id) { return new WP_Error('pd_tt_no_id', 'TikTok gaf geen publish_id.'); }
    return 'https://www.tiktok.com/@praatdeurtje';
}

/* ====================================================================
 * TIKTOK OAUTH — start-handler (admin_init) + REST callback endpoint
 * ==================================================================== */
add_action('admin_init', function () {
    if (get_current_blog_id() !== PD_BLOG) { return; }
    if (!isset($_GET['page']) || 'pd-tiktok' !== sanitize_key((string) $_GET['page'])) { return; }
    if (!current_user_can('manage_options')) { return; }

    if (isset($_GET['pd_tt_action']) && 'start' === sanitize_key((string) $_GET['pd_tt_action'])) {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])), 'pd_tt_start')) {
            wp_die('Ongeldige nonce.');
        }
        $client_key = trim((string) pd_get('pd_tiktok_client_key'));
        if ('' === $client_key) { wp_die('Vul eerst de Client Key in en sla op, dan pas verbinden.'); }
        $state = wp_generate_password(20, false);
        update_blog_option(PD_BLOG, '_pd_tt_oauth_state', $state);
        update_blog_option(PD_BLOG, '_pd_tt_oauth_state_exp', time() + 300);
        $redirect = get_rest_url(PD_BLOG, 'pd/v1/tiktok-callback');
        $url = add_query_arg(array(
            'client_key'    => $client_key,
            'scope'         => 'video.publish',
            'response_type' => 'code',
            'redirect_uri'  => $redirect,
            'state'         => $state,
        ), 'https://www.tiktok.com/v2/auth/authorize/');
        wp_redirect($url);
        exit;
    }
});

add_action('rest_api_init', function () {
    register_rest_route('pd/v1', '/tiktok-callback', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'pd_tt_oauth_callback',
        'permission_callback' => '__return_true',
    ));
});

function pd_tt_oauth_callback(WP_REST_Request $req): void {
    $admin_url = get_admin_url(PD_BLOG, 'admin.php?page=pd-tiktok');
    $error     = (string) $req->get_param('error');

    if ('' !== $error) {
        update_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array('type' => 'error', 'msg' => 'TikTok toegang geweigerd: ' . $error));
        wp_redirect($admin_url);
        exit;
    }

    $code  = (string) $req->get_param('code');
    $state = (string) $req->get_param('state');

    $stored = (string) get_blog_option(PD_BLOG, '_pd_tt_oauth_state', '');
    $exp    = (int) get_blog_option(PD_BLOG, '_pd_tt_oauth_state_exp', 0);
    if ('' === $stored || $stored !== $state || time() > $exp) {
        update_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array('type' => 'error', 'msg' => 'Ongeldige of verlopen state — probeer opnieuw.'));
        wp_redirect($admin_url);
        exit;
    }
    delete_blog_option(PD_BLOG, '_pd_tt_oauth_state');
    delete_blog_option(PD_BLOG, '_pd_tt_oauth_state_exp');

    $client_key    = trim((string) pd_get('pd_tiktok_client_key'));
    $client_secret = trim((string) pd_get('pd_tiktok_client_secret'));
    $redirect      = get_rest_url(PD_BLOG, 'pd/v1/tiktok-callback');

    $resp = wp_remote_post('https://open.tiktokapis.com/v2/oauth/token/', array(
        'timeout' => 30,
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
        'body'    => array(
            'client_key'    => $client_key,
            'client_secret' => $client_secret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirect,
        ),
    ));

    if (is_wp_error($resp)) {
        update_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array('type' => 'error', 'msg' => 'Token-ophalen mislukt: ' . $resp->get_error_message()));
    } else {
        $d = json_decode((string) wp_remote_retrieve_body($resp), true);
        if (!empty($d['refresh_token'])) {
            pd_set('pd_tiktok_refresh_token', (string) $d['refresh_token']);
            delete_blog_option(PD_BLOG, '_pd_tt_access_token_cache');
            update_blog_option(PD_BLOG, '_pd_tt_oauth_connected_at', gmdate('Y-m-d H:i') . ' UTC');
            update_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array('type' => 'success', 'msg' => 'TikTok-account verbonden! Refresh-token opgeslagen.'));
        } else {
            $msg = is_array($d) ? (string) ($d['error_description'] ?? $d['message'] ?? wp_json_encode($d)) : 'Geen token ontvangen.';
            update_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array('type' => 'error', 'msg' => 'Verbinding mislukt: ' . $msg));
        }
    }
    wp_redirect($admin_url);
    exit;
}

/* ====================================================================
 * ADMINPAGINA "TikTok" — koppeling en instellingen
 * ==================================================================== */
function pd_tt_admin_page(): void {
    if (!current_user_can('manage_options')) { return; }

    // Opslaan
    if (isset($_POST['pd_tt_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['pd_tt_nonce'])), 'pd_tt_save')) {
        pd_set('pd_tiktok_client_key',    sanitize_text_field(wp_unslash((string) ($_POST['pd_tiktok_client_key'] ?? ''))));
        pd_set('pd_tiktok_client_secret', sanitize_text_field(wp_unslash((string) ($_POST['pd_tiktok_client_secret'] ?? ''))));
        pd_set('pd_tiktok_privacy',       sanitize_text_field(wp_unslash((string) ($_POST['pd_tiktok_privacy'] ?? 'SELF_ONLY'))));
        pd_set('pd_tiktok_enabled',       isset($_POST['pd_tiktok_enabled']) ? '1' : '0');
        echo '<div class="notice notice-success is-dismissible"><p>Opgeslagen.</p></div>';
    }

    // OAuth notice tonen
    $notice = get_blog_option(PD_BLOG, '_pd_tt_oauth_notice', array());
    if (!empty($notice['msg'])) {
        delete_blog_option(PD_BLOG, '_pd_tt_oauth_notice');
        $cls = ('error' === ($notice['type'] ?? '')) ? 'notice-error' : 'notice-success';
        echo '<div class="notice ' . esc_attr($cls) . ' is-dismissible"><p>' . esc_html((string) $notice['msg']) . '</p></div>';
    }

    $client_key    = esc_attr((string) pd_get('pd_tiktok_client_key'));
    $client_secret = esc_attr((string) pd_get('pd_tiktok_client_secret'));
    $privacy       = (string) pd_get('pd_tiktok_privacy') ?: 'SELF_ONLY';
    $enabled       = '1' === (string) pd_get('pd_tiktok_enabled');
    $refresh       = trim((string) pd_get('pd_tiktok_refresh_token'));
    $connected     = '' !== $refresh;
    $connected_at  = (string) get_blog_option(PD_BLOG, '_pd_tt_oauth_connected_at', '');

    $redirect_uri = get_rest_url(PD_BLOG, 'pd/v1/tiktok-callback');

    echo '<div class="wrap"><h1>Praatdeurtje — TikTok</h1>';
    echo '<p>Elke aflevering wordt automatisch geplaatst op TikTok via de Content Posting API (PULL_FROM_URL). De video moet publiek bereikbaar zijn op het moment van posten.</p>';
    echo '<p><strong>Redirect-URI voor je TikTok developer-app (Web platform):</strong> <code>' . esc_html($redirect_uri) . '</code></p>';

    echo '<form method="post"><table class="form-table">';
    echo '<tr><th>TikTok aan</th><td><label><input type="checkbox" name="pd_tiktok_enabled" value="1"' . checked($enabled, true, false) . '> Publiceer naar TikTok</label></td></tr>';
    echo '<tr><th>Client Key</th><td><input type="text" name="pd_tiktok_client_key" value="' . $client_key . '" class="regular-text" autocomplete="off"></td></tr>';
    echo '<tr><th>Client Secret</th><td><input type="password" name="pd_tiktok_client_secret" value="' . $client_secret . '" class="large-text" autocomplete="off"></td></tr>';
    echo '<tr><th>Privacy</th><td><select name="pd_tiktok_privacy">';
    foreach (array(
        'PUBLIC_TO_EVERYONE'   => 'Publiek (PUBLIC_TO_EVERYONE)',
        'FOLLOWER_OF_CREATOR'  => 'Volgers (FOLLOWER_OF_CREATOR)',
        'MUTUAL_FOLLOW_FRIENDS'=> 'Vrienden (MUTUAL_FOLLOW_FRIENDS)',
        'SELF_ONLY'            => 'Privé / concept (SELF_ONLY)',
    ) as $val => $lbl) {
        echo '<option value="' . esc_attr($val) . '"' . selected($privacy, $val, false) . '>' . esc_html($lbl) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th>Account</th><td>';
    if ($connected) {
        echo '<span style="color:#00a32a">&#10003; Verbonden</span>';
        if ('' !== $connected_at) { echo ' <span style="color:#666">— ' . esc_html($connected_at) . '</span>'; }
        echo '<br><a href="' . esc_url(wp_nonce_url(add_query_arg('pd_tt_action', 'start'), 'pd_tt_start')) . '" style="font-size:12px">Opnieuw verbinden</a>';
    } else {
        echo '<span style="color:#d63638">&#10007; Nog niet verbonden</span>';
        echo '<br><a class="button button-secondary" style="margin-top:6px" href="' . esc_url(wp_nonce_url(add_query_arg('pd_tt_action', 'start'), 'pd_tt_start')) . '">Verbind TikTok-account</a>';
        echo '<p class="description">Sla Client Key + Secret op, dan klikken.</p>';
    }
    echo '</td></tr>';
    echo '</table>';
    wp_nonce_field('pd_tt_save', 'pd_tt_nonce');
    submit_button('Opslaan');
    echo '</form></div>';
}
