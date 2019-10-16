import { Injectable } from '@angular/core';
import { Observable } from 'rxjs/Observable';
import { HttpClient } from '../../../core/utility/http.client';


@Injectable()
export class SupportTicketService {
    constructor(
        private http: HttpClient
    ) { }

    getCmpSupportTickets(data: any): Observable<any> {
        return this.http.post('/company/getCmpSupportTickets', data);
    }

    getCmpSupportTicketsHeadersCount(data: any): Observable<any> {
        return this.http.post('/company/getCmpSupportTicketsHeadersCount', data);
    }

    getFacilityList(data: any): Observable<any> {
        return this.http.get('/facility/getFacilityList', data);
    }

    getSelfCmpSupTickets(data: any): Observable<any> {
        return this.http.post('/company/getSelfCmpSupTickets', data);
    }

    getSelfCmpSupTicketsHeadersCount(data: any): Observable<any> {
        return this.http.get('/company/getSelfCmpSupTicketsHeadersCount', data);
    }

    getFacSupTicketsHeadersCount(data: any): Observable<any> {
        return this.http.get('/facility/getFacSupTicketsHeadersCount', data);
    } 

    getFacSupTickets(data: any): Observable<any> {
        return this.http.post('/facility/getFacSupTickets', data);
    }

    getAllSupportTickets(data: any): Observable<any> {
        return this.http.post('/admin/getAllSupportTickets', data);
    }

    getAllSupportTicketsHeadersCount(data: any): Observable<any> {
        return this.http.post('/admin/getAllSupportTicketsHeadersCount', data);
    }

    getFacilityListByAdmin(data: any): Observable<any> {
        return this.http.get('/admin/getFacilityListByAdmin', data);
    }

    updateSupTicket(data: any): Observable<any> {
        return this.http.post('/admin/updateSupTicket', data);
    }

}
