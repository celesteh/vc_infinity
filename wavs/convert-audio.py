#!/usr/bin/python3

from pydub import AudioSegment
from pydub.effects import normalize
import os
    
# Get directory of this script
dirName = os.path.dirname(os.path.realpath(__file__))

# Get the list of all files in directory tree at given path
listOfFiles = list()
for (dirpath, dirnames, filenames) in os.walk(dirName):
    listOfFiles += [os.path.join(dirpath, file) for file in filenames]
        
        
# Convert the files    
for file_name in listOfFiles:
    
    if file_name.endswith((".wav", ".WAV")):
        root_name = os.path.splitext(file_name)[0]
        flac = root_name + ".flac"
        if (not os.path.isfile(flac)):
            print(file_name)
            sound = AudioSegment.from_wav(file_name)
            normalised = normalize(sound)
            normalised.export(flac,format = "flac")
