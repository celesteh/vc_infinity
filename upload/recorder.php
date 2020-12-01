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
        <title>Audio Recorder</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <script src="volume-meter.js"></script>
        <link rel="stylesheet" href="bootstrap.css">
        <link rel="stylesheet" href="infinity.css">

    </head>
    <body>
        <div class="page-header">
            <h1>Audio Recorder for phones and tablets</h1>
        </div>
<?php include 'nav-menu.php';?>
    <div class="wrapper">
    <div id="controls" class="form-group ">
        <p>New! Record directly from your phone!</p>
        <canvas id="meter" width="500" height="50">Level Meter</canvas>
        <p id="counter" class = "counter">00:00</p>
        <input type="button"  title="Record" id="recordButton" value="&#x23FA;"  class="record-button" />
  	    <input type="button"  title="Stop" id="stopButton" disabled value ="&#x23F9;" class="media-button" />
        <input type="button"  title="Upload" id="uploadButton" disabled value="&#x21E7;"  class="media-button" />
        <input type="button"  title="Try Again" id ="reset" disabled value ="&#x21BB;"  class="media-button" /> 
    </div>
    <div id="player">
        <p id = "p1"></p>
        <p id = "uploadlink"></p>
    </div>
    <div id ="uploading">
        <p id= "p2"></p> 
    </div>
    </div>
        <script type="text/javascript">
            var WIDTH=500;
            var HEIGHT=50;
            var meter;
            var doMetering = true;
            var uploadButton = document.getElementById('uploadButton');
            var audio;
            var blob;
            //var dummy;
            var aplay;
            var startTime;
            var isRecording = false;


            uploadButton.addEventListener("click", uploadAudio);
            
            
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
                      const audioBlob = new Blob(audioChunks, { type : 'audio/wav' });
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
                //const pauseb = document.getElementById('pauseButton');
                const stopb = document.getElementById('stopButton');
                //const playb = document.getElementById('play');
                //uploadButton = document.getElementById('uploadButton');
                //dummy = document.getElementById('dummyButton');
                const recorder = await recordAudio();
                
                const audioContext = new AudioContext();
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true }); // moved from recordAudio()
                const mediaStreamSource = audioContext.createMediaStreamSource(stream);
                meter = createAudioMeter(audioContext);
                //let audio; // filled in end cb
                //let blob;

                //playb.style.visibility = 'hidden';
                //upld.style.visibility = 'hidden';

                //dummy.addEventListener("click", clicked);

                const recStart = e => {
                    recorder.start();
                    btn.initialValue = btn.value;
                    //pauseb.initialValue = "Pause";
                    btn.value = "Recording";
                    //pauseb.disabled = false;
                    stopb.disabled = false;

                    // do the timer
                    startTime = new Date().getTime();
                    isRecording = true;

                    var recTimer = setInterval(function() {

                        if (isRecording) {

                            var now = new Date().getTime();
                            var distance = now - startTime;

                            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            if (minutes <= 9 ){
                                minutes = '0' + minutes;
                            }
                            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                            if (seconds <= 9 ){
                                seconds = '0' + seconds;
                            }

                            // Display the result in the element with id="demo"
                            //document.getElementById("counter").innerHTML = minutes + ":" + seconds;
                            var textnode = document.createTextNode(minutes + ":" + seconds);
                            var item = document.getElementById("counter");
                            item.replaceChild(textnode, item.childNodes[0]);



                        } else {
                            clearInterval(recTimer);
                        }
                    }, 1000 );

                    //btn.removeEventListener("mousedown", recStart);
                    //btn.removeEventListener("touchstart", recStart);
                    //stopb.addEventListener("mousedown", recEnd);
                    //stopb.addEventListener("touchstart", recEnd);
                    //pauseb.addEventListener("mousedown", recPause);
                    //pauseb.addEventListener("touchstart", recPause);
                    //upld.addEventListener("mousedown", uploadAudio);
                    //upld.addEventListener("touchstart", uploadAudio);
                    //playb.style.visibility = 'hidden';
                    //upld.style.visibility = 'hidden';
                }

                //const recPause = async e => {
                    //btn.value = btn.initialValue;
                //    audio = await recorder.stop();
                //    pauseb.value = "Paused";
                //}

                const recEnd = async e => {

                    var htmlplayer;
                    var blobURL;
                    const playerDiv = document.getElementById('player');
                    var p1 = document.getElementById('p1');
                    
                    e.preventDefault();
                    
                    try {
                        audioContext.disconnect(meter);
                    } catch (err){}
                    meter.disconnect();
                    await meter.shutdown;
                    meter = null;
                    doMetering = false; // avoid a race condition, maybe
                    btn.value = btn.initialValue;
                    audio = await recorder.stop();
                        
                    blob = await audio.audioBlob;
                    blobURL = await audio.audioUrl;
                    //pauseb.value = pauseb.initialValue;
                    //pauseb.disabled = true;
                        //uploadButton.disabled = false;
                    //dummy.disabled = false;
                        stopb.disabled = true;
                        btn.disabled = true;
                        isRecording = false;

                    
                    
                    //audio.play();
                    //uploadAudio(audio.audioBlob);
                    //btn.removeEventListener("mousedown", recEnd);
                    //btn.removeEventListener("touchstart", recEnd);
                    //btn.addEventListener("mousedown", recStart);
                    //btn.addEventListener("touchstart", recStart);
                    //playb.style.visibility = 'visible';
                    //playb.addEventListener("mousedown", playAudio);
                    //playb.addEventListener("touchstart", playAudio);
                        try {
                            console.log("bloburl")
                            p1 = document.getElementById('p1');
                            //blobURL = audio.audioUrl; //
                            //blobURL = window.URL.createObjectURL(blob);
                            //console.append(blobURL);
                            //htmlplayer = document.createTextNode(`\n<audio controls="controls" src="` + blobURL + `" type="audio/wav" id ="aplay" />\n`);
                            htmlplayer = document.createTextNode("Please listen to the audio before deciding to upload.");
                            //htmlplayer = 
                            p1.appendChild(htmlplayer); 
                            //document.body.innerHTML += `\n<audio controls="controls" src="` + blobURL + `" type="audio/wav" />\n`;
                            //console.log("appended the htmlplayer")

                            //aplay = document.getElementById('aplay');

                            aplay = new Audio(blobURL);
                            aplay.controls = true;
                            playerDiv.appendChild(aplay);
                            aplay.addEventListener("play", listen)

                        } catch(err){console.log("caught error");}
                    //upld.style.visibility = "visible";
                    //upld.style.visibility = 'visible';
                    //document.body.innerHTML += "\nWhat?\n";
                    
                    //document.body.innerHTML += "Test";
                    //dummy.addEventListener("click", clicked);
                    //dummy.addEventListener("touchstart", clicked);
                    //blob.type="audio/wav";

                        console.log(blob.type);
                        //recorder = null;
                        uploadButton.addEventListener("click", uploadAudio);
                        console.log("End of recEnd");
                    
                }
                /*
                const playAudio = async e => {
                    audio.play();
                    upld.style.visibility = 'visible';
                    upld.addEventListener("mousedown", uploadAudio);
                    upld.addEventListener("touchstart", uploadAudio);
                }
                */
               /*
                const uploadAudio = async e => {
                    document.body.innerHTML += "Uploading";
                    uploadButton.disabled = true;
                    //blob = audio.audioBlob;

                    document.body.innerHTML += "\n<p>Uploading...</p>\n";
                    if (blob.size > (10 * Math.pow(1024, 2))) {
                        document.body.innerHTML += "Too big; could not upload";
                        return;
                    }
                    const f = new FormData();
                    f.append("nonce", window.nonce);
                    f.append("x","<?php echo $x ?>");
                    f.append("y", "<?php echo $y ?>");
                    f.append("id", "<?php echo $panel ?>");
 
                    f.append("audio", blob, "blob.wav");

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
                }*/
                

                //btn.addEventListener("mousedown", recStart);
                //btn.addEventListener("touchstart", recStart);
                btn.addEventListener("click", recStart);
                //window.addEventListener("mouseup", recEnd);
                //window.addEventListener("touchend", recEnd);
                //stopb.addEventListener("mousedown", recEnd);
                //stopb.addEventListener("touchstart", recEnd);
                stopb.addEventListener("click", recEnd);
                //pauseb.addEventListener("mousedown", recPause);
                //pauseb.addEventListener("touchstart", recPause);
                
                mediaStreamSource.connect(meter);

                // kick off the visual updating
                drawLoop();
 


            })();

            /*
            function drawBoard(){
    for (var x = 0; x <= bw; x += 40) {
        context.moveTo(0.5 + x + p, p);
        context.lineTo(0.5 + x + p, bh + p);
    }

    for (var x = 0; x <= bh; x += 40) {
        context.moveTo(p, 0.5 + x + p);
        context.lineTo(bw + p, 0.5 + x + p);
    }
    context.strokeStyle = "black";
    context.stroke();
}
        */

        
        function drawLoop( time ) {

            var lc = '#f2f2f2';
            var divs = 5;
            var wd = WIDTH/divs;

            // clear the background
            canvasContext.clearRect(0,0,WIDTH,HEIGHT);
            for (var x = 0; x <= divs, x +=wd) {
                context.moveTo(x, 0);
                context.lineTo(x, HEIGHT);
            }
            context.strokeStyle = lc;
            context.stroke();

            if (doMetering){
                try {
                    // check if we're currently clipping
                    if (meter.checkClipping())
                        canvasContext.fillStyle = "red";
                    else
                        canvasContext.fillStyle = "green";

                    // draw a bar based on the current volume
               
                    canvasContext.fillRect(0, 0, meter.volume*WIDTH*1.4, HEIGHT);
                } catch (err) {
                    canvasContext.clearRect(0,0,WIDTH,HEIGHT);
                    doMetering = false;
                }
                // set up the next visual callback
               
                    rafID = window.requestAnimationFrame( drawLoop );
            }
        }

        async function uploadAudio(){
            var p2 = document.getElementById('p2');
            var msg;

            console.log("Upload");
            //document.body.innerHTML += "Uploading";

            msg = document.createTextNode("Uploading . . . ..");
                            //htmlplayer = 
            p2.appendChild(msg); 

            uploadButton.disabled = true;
            if (! blob) {
                blob = await audio.audioBlob;
            }
                        document.body.innerHTML += "\n<p>Uploading...</p>\n";
                        if (blob.size > (10 * Math.pow(1024, 2))) {
                            document.body.innerHTML += "Too big; could not upload";
                            return;
                        }
                        const f = new FormData();
                        f.append("nonce", window.nonce);
                        f.append("x","<?php echo $x ?>");
                        f.append("y", "<?php echo $y ?>");
                        f.append("id", "<?php echo $panel ?>");
                        //<input type="submit" value="Upload Audio" name="submit">
                        f.append("submit", "UploadAudio");
 
                       f.append("audio", blob, window.nonce + ".wav");

                        fetch("upload.php", {
                            method: "POST",
                            body: f
                        })
                        .then(_ => {
                            //document.body.innerHTML += `
                            //    <br/> <a href="audio.wav">saved; click here</a>
                            //`
                            msg = document.createTextNode("Success!");
                            p2.appendChild(msg); 
                            window.location.replace("submit.php?success=1");
                        });
                    
        }


        function reload (){

            location.reload();

        }

        function listen () {

            uploadButton.addEventListener("click", uploadAudio);
            uploadButton.disabled = false;
            
            reset = document.getElementById('reset');
            reset.addEventListener("click", reload);
            reset.disabled = false;

            //<a id="myLink" title="Click to do something"
            //href="PleaseEnableJavascript.html" onclick="MyFunction();return false;">link text</a>
            var a = document.createElement('a');  
                  
            // Create the text node for anchor element. 
            var link = document.createTextNode("Upload"); 
                  
            // Append the text node to anchor element. 
            a.appendChild(link);  
                  
            // Set the title. 
            a.title = "Upload";  
                  
            // Set the href property. 
            //a.href = "#";
            //var f = () => {uploadAudio(); return false;}
            //a.onclick = uploadAudio();
            a.addEventListener("click", function() {uploadAudio(); return false;});
                  
            // Append the anchor element to the body. 
            var up = document.getElementById('uploadlink');
            up.appendChild(a);

        }

        function clicked() { console.log("clicked");}
        //uploadButton.addEventListener("mousedown", uploadAudio);
        //uploadButton.addEventListener("touchstart", uploadAudio);

        </script>
    </body>
</html>

