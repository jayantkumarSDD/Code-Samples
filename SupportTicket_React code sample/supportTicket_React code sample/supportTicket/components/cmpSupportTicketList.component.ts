import { Component, Inject, Output, ViewChild, EventEmitter } from '@angular/core';
import { Router, ActivatedRoute, Params } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { ConfirmationService } from 'primeng/primeng';
import { AppConfig } from './../../../core/config/app.config';
import { Utills } from './../../../core/utility/utills';
import { TmpStorage } from './../../../core/utility/temp.storage';

import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { Angular2Csv } from 'angular2-csv/Angular2-csv';
import { WebStorage } from '../../../core/utility/web.storage';
import { FacilityService } from "../../facility/services/facility.services";
import { SupportTicketService } from '../services/supportTicket.services';
type AOA = Array<Array<any>>;

@Component({
    selector: 'app-cmpSupportTicket-list',
    preserveWhitespaces: false,
    templateUrl: './view/cmpSupportTicketList.view.html',
    styles: [`
        :host >>> .popover {
            color: #FFFFFF;
            background: #000000;
        }
    `],
    providers: [
        SupportTicketService,
        FacilityService
    ]
})
export class CmpSupportTicketListComponent {
    supTicketInfo: any;
    supTicketViewDialoge: boolean = false;
    screenShot: string;
    displayScreenshot: boolean = false;
    selectFac: any = '';
    cmpResTickets: number = 0;
    cmpTotalSupTickets: number = 0;
    cmpOpenTickets: number = 0;
    display: boolean = false;
    a: any;
    b: any;
    c: any;
    user: any;
    time: Date;
    prevNowPlaying: any;
    constructor(
        public config: AppConfig,
        private storage: WebStorage,
        private supportTicketService: SupportTicketService,
        private router: Router,
        private toaster: ToastrService,
        private confirmationService: ConfirmationService,
        private facility: FacilityService
    ) {

    }
    public asc: string = 'asc';
    public loading: boolean = true;
    public listData: any = [];
    public totalItems: number = 0;
    public exportfile: string = '';

    public body: any = {
        'page': 1,
        'count': this.config.perPageDefault,
        'createdAt': '',
        'sorting': 'createdAt',
        'order': 'asc'
    };

    public sort(field: string, order: any): void {
        if (order == 'asc') {
            this.asc = 'asc';
        } else {
            this.asc = 'desc';
        }
        this.body.sorting = field;
        this.body.order = order;
        this.getSelfCmpSupTickets();
    }

    public pageChanged(event: any): void {
        this.body.page = event.page;
        this.getSelfCmpSupTickets();
    }

    public changePageLimit(pageLimit: any) {
        this.body.count = parseInt(pageLimit);
        this.getSelfCmpSupTickets();
    }

    public getSelfCmpSupTickets() {
        this.loading = true;
        this.supportTicketService.getSelfCmpSupTickets(this.body).subscribe((result) => {
            let rs = result.json();
            if (rs.code == this.config.statusCode.success) {
                this.listData = rs.data.cmpSelfSupData;
                this.totalItems = rs.data.total_count;
            } else {
                this.toaster.error(rs.message);
            }
            this.loading = false;
        });
    }

    public exportXls(): void {
        this.confirmationService.confirm({
            message: 'Are you sure that you want to Export the data to excel?',
            header: 'Confirmation',
            icon: 'fa fa-question-circle',
            accept: () => {
                let wopts: XLSX.WritingOptions = { bookType: 'xlsx', type: 'array' };
                let fileName: string = 'employee_feedback_' + new Date().getTime() + '.xlsx';
                let data: AOA = [
                    [
                        "Date",
                        "Screenshot",
                        "Status",
                        "Support"
                    ]
                ];
    
                this.listData.map((item: any) => {
                    data.push([
                        item.createdAt,
                        (item.isScreenShot == true) ? item.screenShot: 'N/A',
                        (item.resolved == true) ? 'Resolved': 'Unresolved',
                        item.supportTicket 
                    ]);
                });
    
                const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
                const wb: XLSX.WorkBook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                const wbout: ArrayBuffer = XLSX.write(wb, wopts);
                saveAs(new Blob([wbout], { type: 'application/octet-stream' }), fileName);
            },
            reject: () => {
            }
        });
    }

    public exportJson() {
        this.confirmationService.confirm({
            message: 'Are you sure that you want to Export the data to json file?',
            header: 'Confirmation',
            icon: 'fa fa-question-circle',
            accept: () => {
                var data = [];
                for (let i = 0; i < this.listData.length; i++) {
                    data.push({
                        'Date': this.listData[i].createdAt,
                        'Screenshot': this.listData[i].isScreenShot == true ? this.listData[i].screenShot: 'N/A',
                        'Status': this.listData[i].resolved == true ? 'Resolved': 'Unresolved',
                        'Support': this.listData[i].supportTicket
                });
            }
            var textToSave = JSON.stringify({ "header": [["Date", "Facility Name", "Screenshot", "Status", "Support"]], "data": data }),
                filename = 'file.json',
                blob = new Blob([textToSave], { type: "'application/json; charset=utf-8'" });  
                saveAs(blob, filename);
            },
            reject: () => {
            }
        });
    }

    public createCsvFile() {
        this.confirmationService.confirm({
            message: 'Are you sure that you want to Export the data to csv file?',
            header: 'Confirmation',
            icon: 'fa fa-question-circle',
            accept: () => {
                var data = [];
                var options = {
                    fieldSeparator: ',',
                    quoteStrings: '"',
                    decimalseparator: '.',
                    showLabels: true,
                    showTitle: true,
                    useBom: true
                };
                for (let i = 0; i < this.listData.length; i++) {
                    data.push({
                        'Date': this.listData[i].createdAt,
                        'Screenshot': this.listData[i].isScreenShot == true ? this.listData[i].screenShot: 'N/A',
                        'Status': this.listData[i].resolved == true ? 'Resolved': 'Unresolved',
                        'Support': this.listData[i].supportTicket
                    });
                }
                new Angular2Csv(data, 'listData', { headers: Object.keys(data[0]) });
            },
            reject: () => {
            }
        });
    }

    public exportTxt() {
        this.confirmationService.confirm({
            message: 'Are you sure that you want to Export the data to text file?',
            header: 'Confirmation',
            icon: 'fa fa-question-circle',
            accept: () => {
                var data = [];
                for (let i = 0; i < this.listData.length; i++) {
                    data.push({
                        'Date': this.listData[i].createdAt,
                        'Screenshot': this.listData[i].isScreenShot == true ? this.listData[i].screenShot: 'N/A',
                        'Status': this.listData[i].resolved == true ? 'Resolved': 'Unresolved',
                        'Support': this.listData[i].supportTicket
                    });
                }
                var obj = objectToString(data);
                function objectToString(obj) {
                    var str = '';
                    var i = 0;
                    for (var key in obj) {
                        if (obj.hasOwnProperty(key)) {
                            if (typeof obj[key] == 'object') {
                                {
                                    str += key + ' : { ' + objectToString(obj[key]) + '} ' + (i > 0 ? ',' : '');
                                }
                            }
                            else {
                                str += key + ':\'' + obj[key] + '\'' + (i > 0 ? ',' : '');
                            }
                            i++;
                        }
                    }
                    return str;
                }
                var textToSave = obj,
                filename = 'file.txt',
                blob = new Blob([textToSave], { type: "text/plain;charset=utf-8" });
                saveAs(blob, filename);
            },
            reject: () => {
            }
        });
    }

    public exportXml() {
        this.confirmationService.confirm({
            message: 'Are you sure that you want to Export the data to Xml file?',
            header: 'Confirmation',
            icon: 'fa fa-question-circle',
            accept: () => {
                var data = [];
                for (let i = 0; i < this.listData.length; i++) {
                    data.push({
                        'id': this.listData[i]._id,
                        'column-1': this.listData[i].createdAt,
                        'column-3': this.listData[i].isScreenShot == true ? this.listData[i].screenShot: 'N/A',
                        'column-4': this.listData[i].resolved == true ? 'Resolved': 'Unresolved',
                        'column-5': this.listData[i].supportTicket,
                    });
                }
                var obj = JSON.stringify({
                    "_declaration": {
                        "_attributes": {
                            "version": "1.0",
                            "encoding": "utf-8"
                        }
                    },
                    "tabledata": {
                        "field": [
                            [],
                            "Date",
                            "Facility Name",
                            "Screenshot",
                            "Status",
                            "Support"
                        ],
                        "data": {
                            "row": data
                        }
                    }
                })
                this.facility.exportXml(obj).subscribe((result) => {
                    let rs = result.json();
                    var textToSave = rs.data,
                    filename = 'file.xml',
                    blob = new Blob([textToSave], { type: "'application/xml charset=utf-8'" });
                    saveAs(blob, filename);
                })
            },
            reject: () => {
            }
        });
    }

    public exportAll(exportfile) {
        if (exportfile == 'xls') {
            this.exportXls();
        } else if (exportfile == 'json') {
            this.exportJson();
        } else if (exportfile == 'csv') {
            this.createCsvFile();
        } else if (exportfile == 'txt') {
            this.exportTxt();
        } else if (exportfile == 'xml') {
            this.exportXml();
        }
    }

    public getSelfCmpSupTicketsHeadersCount() {
        this.supportTicketService.getSelfCmpSupTicketsHeadersCount({}).subscribe((result) => {
            let rs = result.json();
            if (rs.code == this.config.statusCode.success) {
                this.cmpTotalSupTickets = rs.data.cmpTotalSupTickets;
                this.cmpOpenTickets = rs.data.cmpOpenTickets;
                this.cmpResTickets = rs.data.cmpResTickets;
            } else {
                this.toaster.error(rs.message);
            }
            this.loading = false;
        });
    }

    public viewScreenshot(screenShotPath){
        this.displayScreenshot = true;
        this.screenShot = 'assets/upload/profiles/' + screenShotPath;
    }

    public showDialog() {
        this.display = true;
    }

    public resetSearch(): void {
        this.body.createdAt = '';
        this.getSelfCmpSupTickets();
    }

    public viewSupportTicket(supTicketData){
        this.supTicketViewDialoge = true;
        this.supTicketInfo = supTicketData;
    }

    ngOnInit() {
        this.user = this.storage.get(this.config.token.userKey);
        var stationdate = new Date();
        if (this.prevNowPlaying) {
            clearInterval(this.prevNowPlaying);
        }
        this.prevNowPlaying = setInterval(() => {
            stationdate = new Date(stationdate.setSeconds(stationdate.getSeconds() + 1));
            this.time = stationdate;
        }, 1000);

        this.getSelfCmpSupTickets();
        this.getSelfCmpSupTicketsHeadersCount();
    }
}
