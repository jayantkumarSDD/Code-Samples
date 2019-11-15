import { ApiCallback, ApiContext, ApiEvent, ApiHandler } from '../../shared/api.interfaces';
import { errorCode } from '../../shared/error-codes';
import { ErrorResult, ForbiddenResult, NotFoundResult } from '../../shared/errors';
import { BillingService } from './billing.service';
import { ResponseBuilder } from '../../shared/response-builder';
import { BaseClass } from '../../shared/base-class';
import { fieldJson } from '../../shared/fields';
import { responseMessages } from '../../shared/responses';

/**
 * class - category
 * Categories class handle all CRUD operations of Category module.
 *
 */
export class BillingController extends BaseClass {
    /**
     * passing service in constructor .
     */
    constructor(private _service: BillingService) { super(); }

    /**
     * Function-  getItems
     * get Invoice listing from Xero
     * @return list of invocies
     */
    getItems: ApiHandler = (event: ApiEvent, context: ApiContext, callback: ApiCallback): void => {
        const data = JSON.parse(event.body);
        this._service.getItems(event)
            .then(result => {
                // if (result.message === 'error') {
                //     ResponseBuilder.internalServerError(result.error, callback);
                // } else {

                return ResponseBuilder.ok<any>(result, callback);
                // }
            })
            .catch((error: ErrorResult) => {
                this.catchError(error, callback);
            });
    };

    /**
     * Function-  getItem
     * Get Detail info of Xero billing Invoice
     * @return detail invoice
     */
    getItem: ApiHandler = (event: ApiEvent, context: ApiContext, callback: ApiCallback): void => {
        const data = JSON.parse(event.body);
        this._service.getItem(event)
            .then(result => {
                if (result.message === 'error') {
                    ResponseBuilder.internalServerError(result.error, callback);
                } else {

                    return ResponseBuilder.ok<any>(result, callback);
                }
            })
            .catch((error: ErrorResult) => {
                this.catchError(error, callback);
            });
    };

    getBillingInfo: ApiHandler = (event: ApiEvent, context: ApiContext, callback: ApiCallback): void => {
        this._service.getBillingRecord(event)
            .then(record => {
                this._service.getBillingInfo(record.result.Items[0])
                    .then(result => {
                        return ResponseBuilder.ok<any>(result, callback);
                    })
                    .catch((error: ErrorResult) => {
                        this.catchError(error, callback);
                    });
            })
            .catch((error: ErrorResult) => {
                this.catchError(error, callback);
            });
    };

    /**
     * Function-  createXeroContact
     * create xero billing account
     */
    createXeroContact: ApiHandler = (event: ApiEvent, context: ApiContext, callback: ApiCallback): void => {
        const data = JSON.parse(event.body);

        this._service.getBillingRecord(event)
            .then(record => {
                this._service.createXeroAccount(data, record.result.Items[0])
                    .then(accountRes => {
                        let _email = event.requestContext.authorizer.claims.email;
                        this._service.createXeroContact(data, _email, record.result.Items[0])
                            .then(result => {

                                this._service.updateXeroIds(event, {
                                        contactId: result['data']['ContactID'],
                                        accountCode: accountRes['Accounts'][0].Code
                                    });

                                result['data']['xeroAccountCode'] = accountRes['Accounts'][0].Code;

                                return ResponseBuilder.ok<any>(result, callback);
                            })
                            .catch((error: ErrorResult) => {
                                this.catchError(error, callback);
                            });
                    }).catch((error: ErrorResult) => {
                        this.catchError(error, callback);
                    });
            })
            .catch((error: ErrorResult) => {
                this.catchError(error, callback);
            });
    };
}
