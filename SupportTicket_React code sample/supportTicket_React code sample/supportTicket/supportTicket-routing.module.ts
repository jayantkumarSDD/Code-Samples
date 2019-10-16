import { NgModule } from "@angular/core";
import { Router, Route, Routes, RouterModule } from "@angular/router";
import { SupportTicketComponent } from "./supportTicket.component";
import { SupportTicketListComponent } from "./components/supportTicketList.component";
import { CmpSupportTicketListComponent } from "./components/cmpSupportTicketList.component";
import { FacSupportTicketListComponent } from "./components/facSupportTicketList.component";
import { AdminSupportTicketListComponent } from "./components/adminSupportTicketList.component";

const routes: Routes = [
    {
        path: '', 
        component: SupportTicketComponent,
        children: [
            {
                path: '',
                component: SupportTicketListComponent,
            },
            {
                path: 'cmpSupportTicket',
                component: CmpSupportTicketListComponent,
            },
            {
                path: 'facSupportTickets',
                component: FacSupportTicketListComponent,
            },
            {
                path: 'adminSupportTickets',
                component: AdminSupportTicketListComponent,
            }
        ]
    }
];

@NgModule({
    imports: [RouterModule.forChild(routes)],
    exports: [RouterModule]
})
export class SupportTicketRoutingModule {

}