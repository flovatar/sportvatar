import { colorArray } from "./DiscoHelpers";

class DiscoWave {
    constructor(x, y, wall, effect) {
        this.x = x;
        this.y = y;
        this.effect = effect;
        this.wall = wall;
        this.totalTiles = this.wall.length;
        this.step = 0;
        this.lastStep = -1;
        this.totalSteps = this.effect.steps.length;
        this.speed = 3;
        this.totalFrames = (this.totalSteps - 1) * this.speed;
        this.frame = 0;
        this.wallCols = 8;
        this.wallRows = Math.trunc(wall.length / this.wallCols);
        this.initialIndex = this.getIndex(x, y);
        this.effectInitialIndex = this.getIndex(this.effect.initialX, this.effect.initialY);
        this.tileShift = this.initialIndex - this.effectInitialIndex;
    }

    getIndex (x, y) {
        return y * this.wallRows + x;
    }

    animate() {
        if(this.step === 0 && typeof this.effect.mask !== 'undefined' && this.effect.mask.length) {
            this.effect.mask.forEach((row, y) => {
                row.forEach((col, x) => {
                    const index = this.getIndex(x, y) + this.tileShift;
                    if(index >= 0 && index < this.totalTiles) {
                        if(col === 1) {
                            this.wall[index].waveInit();
                        }
                    }
                });
            });
        }
        this.effect.steps[this.step].forEach((row, y) => {
            row.forEach((col, x) => {
                const index = this.getIndex(x, y) + this.tileShift;
                if(index >= 0 && index < this.totalTiles) {
                    if(col > 0) {
                        const color = col === 9 ? false : colorArray[col - 1];
                        this.wall[index].waveAnimation(color)
                    }
                }
            });
        });
    }

    action() {
        this.step = Math.trunc(this.frame / this.speed);

        if(this.lastStep !== this.step) {
            this.animate();
        }

        this.lastStep = this.step;
        this.frame += 1;
        return this.frame <= this.totalFrames;
    }
}

export default DiscoWave
