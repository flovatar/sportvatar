<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080">
  <g id="airdrop-wrapper" :class="{'loading': showPackAnimation, 'open': showPackOpening}">
      <defs>
          <style>
              svg {
                  background-color: transparent;
              }

              @keyframes shake {
                  0% { transform: rotate(-3deg) }
                  50% { transform: rotate(3deg) }
                  100% { transform: rotate(-3deg) }
              }
              #airdrop-wrapper.loading #airdrop-box {
                  animation-name: shake;
                  animation-duration: .1s;
                  animation-iteration-count: infinite;
                  transform-origin: center;
                  transform-box: fill-box;
              }

              @keyframes blow-side {
                  0% {  }
                  100% { transform: translate(2000px, -20px) rotate(-45deg) scale(3)  }
              }
              #airdrop-wrapper.open #side {
                  animation-name: blow-side;
                  animation-fill-mode: forwards;
                  animation-duration: .5s;
                  animation-iteration-count: 1;
                  transform-origin: center;
                  transform-box: fill-box;
              }

              @keyframes blow-front {
                  0% {  }
                  100% { transform: translate(-2000px, -20px) rotate(-45deg) scale(3) }
              }
              #airdrop-wrapper.open #front {
                  animation-name: blow-front;
                  animation-fill-mode: forwards;
                  animation-duration: .5s;
                  animation-iteration-count: 1;
                  transform-origin: center;
                  transform-box: fill-box;
              }

              @keyframes blow-top {
                  0% {  }
                  100% { transform: translate(0px, -1500px) rotate(-45deg) scale(3) }
              }
              #airdrop-wrapper.open #top {
                  animation-name: blow-top;
                  animation-fill-mode: forwards;
                  animation-duration: .5s;
                  animation-iteration-count: 1;
                  transform-origin: center;
                  transform-box: fill-box;
              }

              #glow {
                  transform: scale(0.1);
                  transform-origin: center;
              }

              @keyframes blast {
                  0% { opacity: 1 }
                  40% { transform: scale(10) }
                  41% { transform: scale(10); opacity: 1 }
                  100% { transform: scale(10); opacity: 0 }
              }
              #airdrop-wrapper.open #glow {
                  animation-name: blast;
                  animation-fill-mode: forwards;
                  animation-duration: 2s;
                  animation-iteration-count: 1;
                  transform-origin: center;
                  transform-box: fill-box;
              }

          </style>
          <radialGradient id="airdrop-radial-gradient" cx="952" cy="543" fx="952" fy="543" r="292" gradientUnits="userSpaceOnUse">
              <stop offset="0" stop-color="#fff"/>
              <stop offset=".15" stop-color="#fff" stop-opacity=".99"/>
              <stop offset=".28" stop-color="#fff" stop-opacity=".95"/>
              <stop offset=".4" stop-color="#fff" stop-opacity=".88"/>
              <stop offset=".52" stop-color="#fff" stop-opacity=".78"/>
              <stop offset=".63" stop-color="#fff" stop-opacity=".66"/>
              <stop offset=".74" stop-color="#fff" stop-opacity=".5"/>
              <stop offset=".85" stop-color="#fff" stop-opacity=".32"/>
              <stop offset=".95" stop-color="#fff" stop-opacity=".11"/>
              <stop offset="1" stop-color="#fff" stop-opacity="0"/>
          </radialGradient>
      </defs>
      <circle id="glow" cx="952" cy="543" r="292" style="fill: url(#airdrop-radial-gradient);"/>
      <g id="airdrop-box">
          <g id="top">
              <path d="M738.31,393.35l250.14,48.89,89.84-30.08-249.18-43.73-90.8,24.92Z" style="fill: #e8c495;"/>
              <path d="M829.1,368.43l249.18,43.73,40.1-13.43-243.77-42.8-45.51,12.49Z" style="fill: #ab7b6b;"/>
              <path d="M874.61,355.94l243.77,42.8,63.62-21.3-236.57-40.93-70.82,19.44Z" style="fill: #e8c495;"/>
          </g>
          <g id="front">
              <path d="M738,392.99l250.43,48.85v301.29l-249.38-73.54-1.05-276.6Z" style="fill: #b98d72;"/>
              <path d="M873.32,586.43c5.32-5,4.21-14.87-2.47-22.05-6.69-7.18-16.42-8.96-21.74-3.96-5.32,5-4.21,14.87,2.47,22.05s16.42,8.96,21.74,3.96Z"/>
              <path d="M913.16,582.99c-3.88-1.63-7.69,1.95-8.53,8-.83,6.05,1.62,12.28,5.5,13.91,3.88,1.63,7.69-1.95,8.53-8,.84-6.05-1.62-12.28-5.5-13.91Z"/>
              <path d="M894.68,615.87c-9.86-3.25-17.82-10.15-19.41-16.39,.23,8.56,6.32,17.19,14.26,19.81,3.76,1.23,7.29,.91,10.12-.62,.6-.32,.28-1.48-.43-1.62-1.47-.28-2.99-.67-4.54-1.18h.01Z"/>
              <path d="M874.05,473.26c-42.81-18.06-82.92,6.49-89.61,54.83-6.69,48.35,22.6,102.18,65.4,120.24,42.81,18.06,82.92-6.49,89.61-54.83,6.69-48.35-22.6-102.18-65.4-120.24h0Zm-20.81,19.41c24.72,.12,46.2,5.49,47.98,11.99,1.75,6.41-16.31,11.52-40.51,11.55-.18-1.39-.42-2.67-.68-3.82,19.39,0,33.83-2.63,32.42-7.77-1.43-5.23-18.14-8.26-38.01-8.36-19.89-.09-35.43,2.79-34.01,8,1.13,4.12,12.11,6.73,26.45,7.71-.82,.99-1.76,2.13-2.87,3.44-17.56-1.74-30.95-6.12-32.33-11.19-1.78-6.5,16.83-11.68,41.56-11.55h0Zm44.43,132.47c-5.13,1.85-11.08,2.45-17.68,1.8-6.61-.67-13.75-2.6-21.19-5.74-6.22-2.62-12.19-5.9-17.73-9.71-5.58-3.84-10.59-8.14-14.91-12.8-8.9-9.55-14.63-20.24-16.63-30.95-.45-.12-.9-.26-1.34-.45-5.58-2.35-9.29-10.09-8.29-17.28,1-7.19,6.32-11.13,11.89-8.77,.2,.09,.41,.18,.61,.28,.1-.23,.22-.46,.32-.69,4.81-11.16,14.63-18.33,26.97-20.38l-2.78,10,7.61-10.7c4.72-5.68,9.36-11.27,9.52-11.53,.33-.53,1.09-2.66,1.73-1.12,.51,1.21,2.57,6.72,2.62,13.89,3.88,.81,7.75,2.03,11.61,3.65l.12,.05c5.07,2.13,9.93,4.88,14.52,8.2,10.07,7.14,18.42,16.57,24.1,26.88-2.43-6.46-4.43-10.57-6.65-13.97,1.75-1.59,3.38-3.33,4.89-5.22,.91-1.14,1.68-2.56,2.85-3.37,1.11-.77,1.96,1.08,2.24,2.29v.04c.59,2.49,.95,5.04,1.18,7.52,.31,3.2,.37,6.35,.19,9.43-.1,1.58-.93,7.35-1.47,10.24,1.45,3.37,2.62,6.77,3.5,10.21,.43,1.71,.81,3.44,1.1,5.17,3.55,3.44,5.52,9.7,4.67,15.9-.74,5.37-3.38,9.08-6.7,10.14-3.21,8.39-8.88,14.11-16.88,17.01h.01Z"/>
          </g>
          <g id="side">
              <path d="M987.84,442.13l.61,301.37,87.29-39.47V412.7l-87.91,29.43Z" style="fill: #dbb087;"/>
              <path d="M1075.75,412.7v291.33l41.64-19.7V397.67l-41.64,15.03Z" style="fill: #ab7b6b;"/>
              <path d="M1117.4,397.67v286.67l62.42-29.51V375.13l-62.42,22.53Z" style="fill: #dbb087;"/>
              <path d="M1127.21,426.51l-2.15-1.81,10.41-17.37,10.19,8.61-2.2,3.66-6.52-5.51-.27,34.06-3.07,1.31,.27-34.06-6.66,11.13h0Zm34.32-22.91l-.27,34.06,3.07-1.31,.28-34.06,6.52,5.51,2.2-3.66-10.19-8.61-10.41,17.37,2.15,1.81,6.66-11.13h-.01Zm-35.71,62.66l46.08-19.62,.03-3.87-46.08,19.62-.03,3.87h0Z"/>
              <path d="M1017.12,702.03v-5.72l39.02-26.35v5.43l-7.64,5v6.99l7.61-2.39v5.43l-39,11.59h0Zm26.74-18.69l-17.19,11.15,17.18-5.55v-5.6h.01Z"/>
              <path d="M1017.2,685.97v-5.38l38.96-19v5.38l-38.96,19Z"/>
              <path d="M1017.21,675.1v-8.33c.01-5.1,3.34-10.28,9.52-13.28l9.17-4.47c4.36-2.13,6.96-1.73,8.29,.23l12-10.29v5.88l-10.5,8.67v2.27l10.5-5.13v5.43l-38.96,19h-.01Zm23.32-19.03c0-2.11-.72-3.65-4.96-1.57l-8.84,4.32c-3.48,1.7-4.31,4.05-4.31,6.1v2.22l18.12-8.84v-2.22h-.01Z"/>
              <path d="M1017.22,653.35v-8.54c.01-5.1,3.44-10.33,9.68-13.37l19.67-9.6c6.29-3.07,9.61-1.14,9.61,3.97v8.54l-38.96,19Zm33.82-24.37c0-1.99-.88-3.56-4.53-1.79l-19.61,9.57c-3.53,1.72-4.48,4.12-4.48,6.18v2.49l28.61-13.95v-2.49h0Z"/>
              <path d="M1017.25,631.06v-8.33c0-5.1,3.33-10.26,9.52-13.28l9.17-4.47c4.36-2.13,6.96-1.73,8.29,.23l12-10.28v5.88l-10.5,8.67v2.27l10.5-5.13v5.43l-38.96,19h-.01Zm23.32-19.04c0-2.11-.72-3.65-4.96-1.57l-8.84,4.32c-3.48,1.7-4.31,4.05-4.31,6.09v2.22l18.12-8.84v-2.22h-.01Z"/>
              <path d="M1047.66,594.88l-21.72,10.59c-5.31,2.58-9.01,.83-9.01-4.32s3.71-10.51,9.02-13.11l21.72-10.59c5.24-2.56,8.95-.76,8.94,4.34,0,5.17-3.71,10.52-8.95,13.08Zm3.76-10.55c0-1.66-.88-2.84-3.59-1.53l-22.1,10.78c-2.71,1.32-3.59,3.3-3.59,5.02s.88,2.9,3.58,1.58l22.1-10.78c2.71-1.32,3.59-3.37,3.59-5.07h.01Z"/>
              <path d="M1017.3,587.18v-8.32c.01-5.1,3.39-10.3,9.46-13.26l10-4.88c6.08-2.97,9.34-1,9.32,4.1v2.89l10.17-4.96v5.43l-38.96,19Zm23.65-19.19c0-1.99-.82-3.58-4.31-1.89l-9.84,4.79c-3.48,1.7-4.31,4.05-4.31,6.09v2.22l18.44-9.01v-2.22h.01Z"/>
          </g>
      </g>
  </g>
</svg>
