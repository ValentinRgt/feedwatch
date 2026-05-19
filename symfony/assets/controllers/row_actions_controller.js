import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';

export default class extends Controller {
    confirm(event) {
        event.preventDefault();

        const form = event.target.closest('form');
        const { title, text, confirm, cancel } = event.params;

        Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirm,
            cancelButtonText: cancel,
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#1f2937',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
}
