import { NgModule } from "@angular/core";
import { CommonModule } from "@angular/common";
import { FormsModule, ReactiveFormsModule } from '@angular/forms';

import {
    PaginationModule
} from 'ngx-bootstrap';
import {
    ConfirmDialogModule,
    ConfirmationService,
    DialogModule,
    CalendarModule,
    ProgressSpinnerModule
} from 'primeng/primeng';

import { ImageCropperModule } from 'ngx-image-cropper';
import { DataTableModule } from 'primeng/primeng';
import { MultiSelectModule } from 'primeng/primeng';
import { PopoverModule } from "ngx-popover";
import { SupportTicketListComponent } from "./components/supportTicketList.component";
import { AdminSupportTicketListComponent } from "./components/adminSupportTicketList.component";
import { CmpSupportTicketListComponent } from "./components/cmpSupportTicketList.component";
import { FacSupportTicketListComponent } from "./components/facSupportTicketList.component";
import { SupportTicketRoutingModule } from "./supportTicket-routing.module";
import { CountoModule } from "angular2-counto";
import { SupportTicketComponent } from "./supportTicket.component";


@NgModule({
    imports: [
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        PaginationModule.forRoot(),
        ConfirmDialogModule,
        ImageCropperModule,
        DataTableModule,
        MultiSelectModule,
        ProgressSpinnerModule,
        DialogModule,
        CalendarModule,
        PopoverModule,
        SupportTicketRoutingModule,
        CountoModule
    ],
    declarations: [
        SupportTicketComponent,
        SupportTicketListComponent,
        AdminSupportTicketListComponent,
        CmpSupportTicketListComponent,
        FacSupportTicketListComponent
    ],
    providers: [
        ConfirmationService
    ]
})
export class SupportTicketModule { }