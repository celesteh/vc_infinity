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

// map between two linear ranges
function linlin (x, lowIn, highIn, lowOut, highOut){
    var result = lowOut;
    if(x <= lowIn){
        result = lowOut;
    } else { 
        if (x>=highIn) {
            result = highOut;
        } else {
            result = ((x-lowIn)/(highIn-lowIn)) * ((highOut-lowOut)) + lowOut;
        }
    }
    return result;
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

function getNRandSortedExcluding(audio_json, n, excluded){

    var arr = [];
    var picked;
    var tries = 0;

    while((arr.length < n) && (tries < (n*3))){
        picked = audio_json[Math.floor(Math.random() * audio_json.length)];
        if (!excluded.includes(picked)){
            arr.push(picked);
        } else {console.log("already present");}
        tries++;
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

function Ramp (){

    this.mintime = 40;
    this.maxtime = 300;
    this.start = 0;
    this.middle = 0;
    this.end = 0;
    this.duration = 0;
    this.startTime = 0;

    this.init = function(start, startTime) {

        if(start){
            this.start = start;
        } else {
            this.start = rrand(1,5);
        }
        
        this.end = rrand(1,5);
        this.middle = ((this.end - this.start) / 2) + this.start;


        this.duration = rrand(this.mintime,this.maxtime) * 1000;

        if(startTime){
            this.startTime = startTime;
        } else {
            var d = new Date();
            this.startTime = d.getTime();
        }
    };

    this.init();

    this.value  = function() {
        var d = new Date();
        var time = d.getTime();
        var elapsed = time - this.startTime;
        //var durmilis = this.duration * 1000;

        // figureing out the current time might include the computer going to sleep, etc

        
        if (elapsed > this.duration){
            if ((elapsed) < (this.maxtime * 2)){
                var flag = true;
                while(flag){
                    this.init(this.end, this.startTime + this.duration);
                    flag = ((time - this.startTime)> this.duration);
                }
            } else {
                this.init()
            }
        }

        var slope = (this.end - this.start) / this.duration; //slope = rise over run
        return ((slope * elapsed)+ this.start); // rise = slope * run

    }
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
    this.times = 1;

    this.whenFinished = new function(){  };
 
    this.arr = json_arr;
    //this.clip = "";

    
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
        this.clip.stereo(pos);
    };
    this.amp = function(volume) {
        this.amplitude = volume;
        this.clip.volume(volume);
    };
    this.setRate = function(rate){
        this.rate = rate;
        this.clip.rate(rate);
    };

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
    };

    this.isCopy = function(other){
        return (this.src == other.src);
    };

    this.loaded = function() {
        return (this.clip.state() == "loaded");
    };

    this.load = function() {
        this.clip = new Howl ({ 
            src: this.src, 
            preload: true,
            volume: this.amplitude,
            loop: false
        });

        //this.clip.on("load",function(){
        //    dur = this.clip.duration();
        //});
        //this.clip.on("end", function(){
        //    this.unload();
        //})
    };

    this.unload = function(){
        this.clip.unload();
    };

    //this.load();
    this.clip = new Howl ({ 
        src: this.src, 
        preload: true,
        volume: this.amplitude,
        loop: false
    });

    //this.clip.on("load",function(){
    //    this.dur = this.clip.duration();
    //});
    //this.clip.on("end", function(){
    //    this.unload();
    //});

    this.clip.on("end", function(){
        this.times = this.times -1;
        this.loop = (this.times> 1);
        if(this.loop == false) {
            if (valid(this.whenFinished)){
                this.whenFinished();
            }
        }
    });

    this.dur = function(){
        return this.clip.duration();
    }

    this.setRepeats = function(n){
        this.times = n;
        this.clip.loop = (n>1)
    }

    this.setFinished = function(doneAction){
        this.whenFinished = doneAction;
    }
}
