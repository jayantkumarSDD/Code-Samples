import { Component } from "@angular/core";

@Component({
    template: `
        <p-confirmDialog header="Confirmation" icon="fa fa-question-circle"></p-confirmDialog>
        <router-outlet></router-outlet>
    `,
})
export class SupportTicketComponent {
    
}

