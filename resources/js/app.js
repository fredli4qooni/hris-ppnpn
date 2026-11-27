import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('profilePhotoCropper', () => ({
    showCropModal: false,
    isSubmitting: false,
    cropSize: 240,
    outputSize: 480,
    image: null,
    objectUrl: null,
    zoom: 1,
    rotation: 0,
    offsetX: 0,
    offsetY: 0,
    isDragging: false,
    dragStartX: 0,
    dragStartY: 0,
    startOffsetX: 0,
    startOffsetY: 0,
    clientError: null,

    onFileSelected(event) {
        const input = event.target;
        const file = input.files && input.files[0];
        this.clientError = null;

        if (!file) {
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.clientError = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
            input.value = '';
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            this.clientError = 'Ukuran berkas awal terlalu besar (maksimal 10 MB sebelum potong).';
            input.value = '';
            return;
        }

        this.revokeObjectUrl();
        this.objectUrl = URL.createObjectURL(file);

        const img = new Image();
        img.onload = () => {
            this.image = img;
            this.resetTransform();
            this.showCropModal = true;
            this.$nextTick(() => this.drawPreview());
        };
        img.onerror = () => {
            this.clientError = 'Berkas gambar tidak dapat dibaca.';
            this.revokeObjectUrl();
            input.value = '';
        };
        img.src = this.objectUrl;
    },

    getBaseScale() {
        if (!this.image) {
            return 1;
        }
        const rotated = this.rotation % 180 !== 0;
        const rotW = rotated ? this.image.naturalHeight : this.image.naturalWidth;
        const rotH = rotated ? this.image.naturalWidth : this.image.naturalHeight;

        return Math.max(this.cropSize / Math.max(1, rotW), this.cropSize / Math.max(1, rotH));
    },

    clampOffsets() {
        if (!this.image) {
            this.offsetX = 0;
            this.offsetY = 0;
            return;
        }

        const rotated = this.rotation % 180 !== 0;
        const rotW = rotated ? this.image.naturalHeight : this.image.naturalWidth;
        const rotH = rotated ? this.image.naturalWidth : this.image.naturalHeight;
        const scale = this.getBaseScale() * this.zoom;

        const maxX = Math.max(0, (rotW * scale - this.cropSize) / 2);
        const maxY = Math.max(0, (rotH * scale - this.cropSize) / 2);

        this.offsetX = Math.min(maxX, Math.max(-maxX, this.offsetX));
        this.offsetY = Math.min(maxY, Math.max(-maxY, this.offsetY));
    },

    resetTransform() {
        this.zoom = 1;
        this.rotation = 0;
        this.offsetX = 0;
        this.offsetY = 0;
        this.clampOffsets();
        this.drawPreview();
    },

    rotateRight() {
        this.rotation = (this.rotation + 90) % 360;
        this.offsetX = 0;
        this.offsetY = 0;
        this.clampOffsets();
        this.drawPreview();
    },

    onZoomChange() {
        this.clampOffsets();
        this.drawPreview();
    },

    startDrag(event) {
        if (!this.image) {
            return;
        }
        this.isDragging = true;
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
        this.startOffsetX = this.offsetX;
        this.startOffsetY = this.offsetY;

        if (event.currentTarget && event.currentTarget.setPointerCapture) {
            try {
                event.currentTarget.setPointerCapture(event.pointerId);
            } catch (e) {}
        }
    },

    onDrag(event) {
        if (!this.isDragging) {
            return;
        }
        this.offsetX = this.startOffsetX + (event.clientX - this.dragStartX);
        this.offsetY = this.startOffsetY + (event.clientY - this.dragStartY);
        this.clampOffsets();
        this.drawPreview();
    },

    endDrag() {
        this.isDragging = false;
    },

    renderToCanvas(canvas, targetSize) {
        if (!canvas || !this.image) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const factor = targetSize / this.cropSize;
        const scale = this.getBaseScale() * this.zoom * factor;

        ctx.clearRect(0, 0, targetSize, targetSize);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, targetSize, targetSize);

        ctx.save();
        ctx.translate(targetSize / 2 + this.offsetX * factor, targetSize / 2 + this.offsetY * factor);
        ctx.rotate((this.rotation * Math.PI) / 180);
        ctx.scale(scale, scale);
        ctx.drawImage(this.image, -this.image.naturalWidth / 2, -this.image.naturalHeight / 2);
        ctx.restore();
    },

    drawPreview() {
        this.renderToCanvas(this.$refs.previewCanvas, this.cropSize);
    },

    cancelCrop() {
        this.showCropModal = false;
        this.image = null;
        this.isDragging = false;
        this.revokeObjectUrl();
        if (this.$refs.photoInput) {
            this.$refs.photoInput.value = '';
        }
    },

    confirmCrop() {
        if (!this.image || this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = this.outputSize;
        exportCanvas.height = this.outputSize;
        this.renderToCanvas(exportCanvas, this.outputSize);

        exportCanvas.toBlob((blob) => {
            if (!blob) {
                this.clientError = 'Gagal memproses hasil pemotongan foto.';
                this.isSubmitting = false;
                return;
            }

            try {
                const croppedFile = new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(croppedFile);
                this.$refs.photoInput.files = dt.files;
                this.revokeObjectUrl();
                this.$refs.photoUploadForm.submit();
            } catch (e) {
                this.clientError = 'Browser tidak mendukung unggahan foto terpotong.';
                this.isSubmitting = false;
            }
        }, 'image/jpeg', 0.9);
    },

    revokeObjectUrl() {
        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }
    },
}));

Alpine.start();
