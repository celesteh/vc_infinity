//#import {Howl, Howler} from 'howler/howler.js/dist/howler.js';

function loadJSON(path, success, error)
{
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function()
    {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                if (success)
                    success(JSON.parse(xhr.responseText));
            } else {
                if (error)
                    error(xhr);
            }
        }
    };
    xhr.open("GET", path, true);
    xhr.send();
}

function valid (testee){
    var is_valid = false;
    var is_valid = (typeof testee != 'undefined');
    if (is_valid){
        if (typeof testee == "string") {
            testee = testee.trim();
            if (testee.length < 1){
                is_valid = false;
            }
        }
    };
    return is_valid;
}

function sortAudioByX(audio_json){
    //[$x, $y, $dir, $wav, $flac, $meta, $tags, $dur, $user]
    //var keys = Object.keys(users);
    var sortedflacs = audio_json.sort(function(a, b) {
        var ax = a[0];
        var bx = b[0];
        if (ax < bx) {
            return -1; //nameA comes first
        }
        if (ax > bx) {
            return 1; // nameB comes first
        }
        return 0;  // names must be equal
    });
    return sortedflacs;
}

function getNRandSorted(audio_json, n){

    var arr = [];
    for(i=0; i<n ; i++){
        arr.push(audio_json[Math.floor(Math.random() * audio_json.length)]);
    };

    arr = sortAudioByX(arr);
    return arr;
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function rrand(low, high){
    var mulval = high-low;
    var num = Math.random() * mulval;
    num = num + low;
    return num;
}


function AudioClip (json_arr){
    //[$x, $y, $dir, $wav, $flac, $meta, $tags, $dur, $user]
    this.x = json_arr[0];
    this.y = json_arr[1];
    this.tags = json_arr[6];
    this.scores = json_arr[5];
    this.artist = json_arr[8];
    this.pos = 0;
    this.ampltiude = 0.5;
    this.rate = 1;
    this.src = "processed_audio/" + [json_arr[2] + "/" + json_arr[4]];
    this.dur = -1;
 
    this.arr = json_arr;

    
    //this.clip = new Howl ({ 
    //    src: this.src, 
    //    preload: true,
    //    volume: this.amplitude,
    //    loop: false ,
    //    onload:  function () {
    //        /*
    //        queue.push(howl);
    //        if (! started){
    //            started = true;
    //            //var active = queue.shift();
    //            setTimeout(player, rrand(200, 3000));
    //        }
    //        */
    //        this.dur = clip.duration();
    //    },
    //    onend: function() {
    //        this.unload();
    //    }
    //});

    this.pan = function(pos) { 
        this.pos = pos;
        clip.stereo(pos);
    };
    this.amp = function(volume) {
        this.amplitude = volume;
        clip.volume(volume);
    };
    this.setRate = function(rate){
        this.rate = rate;
        clip.rate(rate);
    }

    this.xcomparesort = function(a,b){
        if (a.x < b.x) {
            return -1; //nameA comes first
        }
        if (a.x > b.x) {
            return 1; // nameB comes first
        }
        return 0;  // names must be equal
    };

    this.xcompare = function(other){
        return this.xcomparesort(this,other);
    }

    this.isCopy = function(other){
        return (this.src == other.src);
    }

    this.loaded = function() {
        return (clip.state() == "loaded");
    }

    this.load() = function() {
        this.clip = new Howl ({ 
            src: this.src, 
            preload: true,
            volume: this.amplitude,
            loop: false
        });

        this.clip.on("load",function(){
            this.dur = clip.duration();
        });
        this.clip.on("end", function(){
            this.unload();
        })
    }

    this.unload() = function(){
        clip.unload();
    }

    this.load();
    
}
