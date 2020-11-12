<?php
 // Initialize the session
 session_start();
 include_once "config.php";
 include_once "functions.php";
  
 
 // Check if the user is logged in, if not then redirect him to login page
 if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
     header("location: login.php");
     exit;
 }
 
 
 if (! lazy_power_check($_SESSION["id"], $pdo, 20)){
     header("location: index.php");
 }
 
 if(isset($_SESSION["page_called"])){
     $page_called = $_SESSION["page_called"];
 } else {
     $page_called = get_page_called_for_user($_SESSION["id"], $pdo);
     $_SESSION["page_called"] = $page_called;
 }
 $upper = ucfirst($page_called);
 
 $selected = false;
 $panel = -1;
 $upload = false;
 $ok = false;
 $active = false;

 $correct_nonce = verify_nonce();
//if (! $correct_nonce){
$_SESSION['nonce'] = set_nonce();
    //}


 if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST["submit"]) && isset($_POST["id"]) && isset($_POST["x"]) && isset($_POST["y"])) {



        // is this an upload?

        // was the nonce ok?


        if (! $correct_nonce){
            //header("location: submit.php?err=doubled");
        }
        
        $panel = trim($_POST["id"]);
        $x = trim($_POST["x"]);
        $y = trim($_POST["y"]);

        $ok = true;
    }
}

if (! $ok){
    header("location: submit.php");
}


 
 
 ?>
<!DOCYTPE html>
<html>
    <head>
        <title>Simple audio recording demo</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <script src="volume-meter.js"></script>
    </head>
    <body>
        <p>You can try recording, but uploading does not yet work. Coming soon!</p>
        <canvas id="meter" width="500" height="50">Level Meter</canvas>
        <input type="button" class="btn" id="recordButton" value="Record" />
  	    <input type="button" class="btn" id="pauseButton" disabled value="Pause" />
  	    <input type="button" class="btn" id="stopButton" disabled value ="Stop" />
        <input type="button" class="btn" id="play" value="Play" />
        <input type="button" class="btn" id="upload" value="Upload" />

        <script type="text/javascript">
            var WIDTH=500;
            var HEIGHT=50;
            var meter;

            window.nonce = "<?php echo $_SESSION['nonce']; ?>"
            const canvasContext = document.getElementById( "meter" ).getContext("2d"); 
            // courtesy https://medium.com/@bryanjenningz/how-to-record-and-play-audio-in-javascript-faa1b2b3e49b
            const recordAudio = () => {
              return new Promise(async resolve => {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mediaRecorder = new MediaRecorder(stream);
                const audioChunks = [];

                mediaRecorder.addEventListener("dataavailable", event => {
                  audioChunks.push(event.data);
                });

                const start = () => mediaRecorder.start();

                const stop = () =>
                  new Promise(resolve => {
                    mediaRecorder.addEventListener("stop", () => {
                      const audioBlob = new Blob(audioChunks);
                      const audioUrl = URL.createObjectURL(audioBlob);
                      const audio = new Audio(audioUrl);
                      const play = () => audio.play();
                      resolve({ audioBlob, audioUrl, play });
                    });

                    mediaRecorder.stop();
                  });

                resolve({ start, stop });
              });
            }

            /* simple timeout */
            const sleep = time => new Promise(resolve => setTimeout(resolve, time));

            /* init */
            (async () => {
                const btn = document.getElementById('recordButton');//document.querySelector("input");
                const pauseb = document.getElementById('pauseButton');
                const stopb = document.getElementById('stopButton');
                const playb = document.getElementById('play');
                const upld = document.getElementById('upload');
                const recorder = await recordAudio();
                
                const audioContext = new AudioContext();
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true }); // moved from recordAudio()
                const mediaStreamSource = audioContext.createMediaStreamSource(stream);
                meter = createAudioMeter(audioContext);
                let audio; // filled in end cb
                let blob;

                playb.style.visibility = 'hidden';
                upld.style.visibility = 'hidden';

                const recStart = e => {
                    recorder.start();
                    btn.initialValue = btn.value;
                    pauseb.initialValue = "Pause";
                    btn.value = "Recording";
                    pauseb.disabled = false;
                    stopb.disabled = false;
                    //btn.removeEventListener("mousedown", recStart);
                    //btn.removeEventListener("touchstart", recStart);
                    stopb.addEventListener("mousedown", recEnd);
                    stopb.addEventListener("touchstart", recEnd);
                    pauseb.addEventListener("mousedown", recPause);
                    pauseb.addEventListener("touchstart", recPause);
                    playb.style.visibility = 'hidden';
                    upld.style.visibility = 'hidden';
                }

                const recPause = async e => {
                    //btn.value = btn.initialValue;
                    audio = await recorder.stop();
                    pauseb.value = "Paused";
                }

                const recEnd = async e => {
                    btn.value = btn.initialValue;
                    audio = await recorder.stop();
                    blob = audio.audioBlob;
                    pauseb.value = pauseb.initialValue;
                    pauseb.disabled = true;
                    stopb.disabled = true;
                    //audio.play();
                    //uploadAudio(audio.audioBlob);
                    //btn.removeEventListener("mousedown", recEnd);
                    //btn.removeEventListener("touchstart", recEnd);
                    //btn.addEventListener("mousedown", recStart);
                    //btn.addEventListener("touchstart", recStart);
                    //playb.style.visibility = 'visible';
                    //playb.addEventListener("mousedown", playAudio);
                    //playb.addEventListener("touchstart", playAudio);
                    blobURL = window.URL.createObjectURL(blob);
                    document.body.innerHTML += ` 
                    <audio controls="controls" src="` + blobURL + `" type="audio/wav" />\n`
                }

                const playAudio = async e => {
                    audio.play();
                    upld.style.visibility = 'visible';
                    upld.addEventListener("mousedown", uploadAudio);
                    upld.addEventListener("touchstart", uploadAudio);
                }

                const uploadAudio = async e => {
                    //blob = audio.audioBlob;

                    if (blob.size > (10 * Math.pow(1024, 2))) {
                        document.body.innerHTML += "Too big; could not upload";
                        return;
                    }
                    const f = new FormData();
                    f.append("nonce", window.nonce);
                    f.append("x","<?php echo $x ?>");
                    f.append("y", "<?php echo $y ?>");
                    f.append("id", "<?php echo $panel ?>");
 
                    f.append("audio", blob);

                    fetch("upload.php", {
                        method: "POST",
                        body: f
                    })
                    .then(_ => {
                        document.body.innerHTML += `
                            <br/> <a href="audio.wav">saved; click here</a>
                        `
                    });
                    //;
                }


                btn.addEventListener("mousedown", recStart);
                btn.addEventListener("touchstart", recStart);
                //window.addEventListener("mouseup", recEnd);
                //window.addEventListener("touchend", recEnd);

                
                mediaStreamSource.connect(meter);

                // kick off the visual updating
                drawLoop();
 


            })();

        function drawLoop( time ) {
            // clear the background
            canvasContext.clearRect(0,0,WIDTH,HEIGHT);

            // check if we're currently clipping
            if (meter.checkClipping())
                canvasContext.fillStyle = "red";
            else
                canvasContext.fillStyle = "green";

            // draw a bar based on the current volume
            canvasContext.fillRect(0, 0, meter.volume*WIDTH*1.4, HEIGHT);

            // set up the next visual callback
            rafID = window.requestAnimationFrame( drawLoop );
        }
        </script>
    </body>
</html>

