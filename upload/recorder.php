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

 $_SESSION['nonce'] [] = set_nonce();


 if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST["submit"]) && isset($_POST["id"]) && isset($_POST["x"]) && isset($_POST["y"])) {



        // is this an upload?

        // was the nonce ok?


        if (! $correct_nonce){
            header("location: submit.php?err=doubled");
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
    </head>
    <body>
        <input type="button" class="btn" value="click and hold to record" />
        <script type="text/javascript">
            window.nonce = "<?php echo $_SESSION['nonce']; ?>"
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
                const btn = document.querySelector("input");
                const recorder = await recordAudio();
                let audio; // filled in end cb

                const recStart = e => {
                    recorder.start();
                    btn.initialValue = btn.value;
                    btn.value = "recording...";
                }
                const recEnd = async e => {
                    btn.value = btn.initialValue;
                    audio = await recorder.stop();
                    audio.play();
                    uploadAudio(audio.audioBlob);
                }

                const uploadAudio = a => {
                    if (a.size > (10 * Math.pow(1024, 2))) {
                        document.body.innerHTML += "Too big; could not upload";
                        return;
                    }
                    const f = new FormData();
                    f.append("nonce", window.nonce);
                    f.append("x","<?php echo $x ?>");
                    f.append("y", "<?php echo $y ?>");
                    f.append("id", "<?php echo $panel ?>");
 
                    f.append("audio", a);

                    fetch("upload.php", {
                        method: "POST",
                        body: f
                    })
                    //.then(_ => {
                    //    document.body.innerHTML += `
                    //        <br/> <a href="audio.wav">saved; click here</a>
                    //    `
                    //});
                    ;
                }


                btn.addEventListener("mousedown", recStart);
                btn.addEventListener("touchstart", recStart);
                window.addEventListener("mouseup", recEnd);
                window.addEventListener("touchend", recEnd);
            })();
        </script>
    </body>
</html>

