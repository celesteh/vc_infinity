#!/bin/bash


prompt_confirm() {
  while true; do
    read -r -n 1 -p "${1:-Continue?} [y/n]: " REPLY
    case $REPLY in
      [yY]) echo ; return 0 ;;
      [nN]) echo ; return 1 ;;
      *) printf " \033[31m %s \n\033[0m" "invalid input"
    esac 
  done  
}

prompt_confirm "Delete local flacs?" && rm *.flac && rm */*.flac

prompt_confirm "Do converstion?" && ./convert-audio.py

#prompt_confirm "Move local flacs?" && cp ./ -r ../processed_audio && rm *.flac && rm */*.flac

#prompt_confirm "Rsync wavs?" && rsync -avz --exclude '*.zip' --exclude '*.py'  --exclude '*.sh' --exclude '\.*' ./ celesteh@opal9.opalstack.com:/home/celesteh/apps/vc_infinity/wavs/
prompt_confirm "Rsync wavs?" && rsync -avz --exclude '*' --include '*/' --include '*.wav' ./ celesteh@opal9.opalstack.com:/home/celesteh/apps/vc_infinity/wavs/

#cd ../processed_audio
#rm *.wav
#rm */*.wav
#rm *.py
#rm *.sh

#prompt_confirm "Rsync flacs?" && rsync -avz  --exclude '*.zip' --exclude '*.py'  --exclude '*.sh' --exclude '\.*' ./ celesteh@opal9.opalstack.com:/home/celesteh/apps/vc_infinity/processed_audio/
prompt_confirm "Rsync flacs?" && rsync -avz --exclude '*' --include '*/' --include '*.flac' ./ celesteh@opal9.opalstack.com:/home/celesteh/apps/vc_infinity/processed_audio/


prompt_confirm "Update db?" && ssh celesteh@opal9.opalstack.com "cd apps/vc_infinity/upload/ && php80 import_audio.php"
