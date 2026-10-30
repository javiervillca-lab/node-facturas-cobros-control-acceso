import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['filas', 'plantilla'];

    agregar() {
        const fila = this.plantillaTarget.content.cloneNode(true);
        this.filasTarget.appendChild(fila);
    }

    quitar(event) {
        event.target.closest('.fila-concepto').remove();
    }
}
