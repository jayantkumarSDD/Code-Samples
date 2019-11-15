import { ErrorResult, ForbiddenResult, NotFoundResult, PreConditionFailedResult } from '../../shared/errors';
import { CrudService } from '../../services/crud-service';
import { ResponseBuilder } from '../../shared/response-builder';
import { Contact, Invoice, XeroClientConfiguration } from '../../shared/xero-fields';
import { BillingDetails } from '../subscriptions/billingInfo.schema';
import { BaseClass } from '../../shared/base-class';
import { plansJson } from '../../shared/plans';

import * as XeroClient from 'xero-node';
import * as _ from 'underscore';
import * as Path from 'path';

// const XeroClient = require('xero-node').AccountingAPIClient;
// const config = require('./config.json');

export class BillingService {
  baseclass = new BaseClass();
  /**
   * Function- getItems
   */
  async getItems(payload) {

    // You can initialise Private apps directly from your configuration
    let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());

    const result = await xero.invoices.get();

    result.Invoices = _.filter(result.Invoices, res => {
      return res['Contact']['ContactID'] === payload.pathParameters.id;
    });

    return result.Invoices;
  }

  async getBillingRecord(event) {
    let accountID = await this.baseclass.filterID(event);
    let record = await BillingDetails.primaryKey.scan({
      FilterExpression: 'id = :id',
      ExpressionAttributeValues: { ':id': accountID }
    });

    return record;
  }

  /**
   * Function-  updatePlanId
   */
  async updateXeroIds(event, ids) {

    let obj = JSON.parse(event.body);
    let accountID = await this.baseclass.filterID(event);

    let date = new Date();
    let expressionAttributeValues = {};
    let updateExpression = {};

    expressionAttributeValues = {
        ':updatedAt': date.getTime(),
        ':xeroContactId': ids.contactId,
        ':xeroAccountCode': ids.accountCode
    };
    updateExpression =
    'SET updatedAt = :updatedAt, xeroAccountCode = :xeroAccountCode, xeroContactId = :xeroContactId';

    const params = {
        ExpressionAttributeValues: expressionAttributeValues,
        UpdateExpression: updateExpression
    };
    let updateRecord = await BillingDetails.primaryKey.update(accountID, params);

    return updateRecord['__attributes'];
  }

  /**
   * Function- setItem
   */
  async getItem(payload) {
    try {
      return await this.getOnlineInvoice(payload);
    } catch (error) {
      return error;
    }
  }

  async getOnlineInvoice(payload) {
    let invoiceId = payload.pathParameters.id;
    let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());
    let result = await xero.invoices.onlineInvoice.get({ InvoiceID: invoiceId });

    return result;
  }

  /**
   * Function- getItem
   */
  async generateInvoice(data) {

    let _record = await BillingDetails.primaryKey.scan({
      FilterExpression: 'subscriptionId = :subscriptionId',
      ExpressionAttributeValues: { ':subscriptionId': data.subscription.id }
    });

    let _item = _record.result.Items[0];
    console.log('_item==========', data.subscription.id, '=================>', _record, data.subscription);

    if (!_item) {
      return {};
    }

    let _plan = plansJson.filter(_tmpPlan => _tmpPlan.id === _item.planId)[0];
    let _tax = 0;

    if (_item.vatPercentage && !_item.vatNumber) {
      _tax = _plan.price * _item.vatPercentage / 100;
    }
    let date = new Date();

    let _invoiceData = {
      dueDate: '\/Date(' + Date.now() + ')\/',
      description: `Payment for ${_item.usersCount} users`,
      unitAmount: _plan.price,
      tax: _tax,
      totalTax: _tax * _item.usersCount,
      subTotal:  _plan.price * _item.usersCount,
      Quantity: _item.usersCount,
      accountCode: _item.xeroAccountCode,
      total: _plan.price * _item.usersCount
    };

    _invoiceData.total = _invoiceData.subTotal + _invoiceData.totalTax;

    // You can initialise Private apps directly from your configuration
    let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());
    const createSingleInvoiceRequest: Invoice = {
      Type: 'ACCREC',
      Contact: {
        ContactID: _item.xeroContactId
      },
      SubTotal: _invoiceData.subTotal,
      TotalTax: _invoiceData.totalTax,
      Total: _invoiceData.total,
      DueDate: _invoiceData.dueDate,
      Status: 'AUTHORISED',
      LineAmountTypes: 'Exclusive',
      LineItems: [{
        Description: _invoiceData.description,
        Quantity: _invoiceData.Quantity,
        UnitAmount: _invoiceData.unitAmount,
        TaxAmount: _invoiceData.totalTax,
        AccountCode: _invoiceData.accountCode
      }]
    };

    let invoiceRsp = await xero.invoices.create(createSingleInvoiceRequest);

    // let paymentRsp = await xero.payments.create({
    //   Date: _invoiceData.dueDate,
    //   Amount: _invoiceData.total,
    //   Account: { Code: _invoiceData.accountCode },
    //   Invoice: { InvoiceID: invoiceRsp.Invoices[0].InvoiceID }
    // });

    console.log('----------------------------------------', invoiceRsp, invoiceRsp.Invoices[0].ValidationErrors);
    // console.log('----------------------------------------', paymentRsp, paymentRsp.Payments[0].ValidationErrors);

    return invoiceRsp;
  }

  /**
   * Function- getXeroConfig
   */
  getXeroConfig() {
    let config = {
      appType: null,
      consumerKey: process.env.xeroKey,
      consumerSecret: process.env.xeroSceret,
      callbackUrl: process.env.xeroCallbackUrl,
      privateKeyString: process.env.privateKeyString
    };
    config.appType = 'private';

    return config;
  }

  /**
   * Function- createXeroContact
   */
  async createXeroContact(user, emailID, record) {
    let rslt;
    // You can initialise Private apps directly from your configuration
    let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());
    const _contact: Contact = {
      Name: `${user.company} - ${user.lastName} ${user.firstName}`,
      FirstName: user.firstName,
      LastName: user.lastName,
      EmailAddress: emailID,
      IsCustomer: true,
      TaxNumber: user.vatNumber ? user.vatNumber : '',
      Addresses: [
        {
          AddressType: 'POBOX',
          City: user.city,
          PostalCode: user.postCode,
          Country: user.country
        }
      ]
    };

    if (record.hasOwnProperty('xeroContactId')) {
      rslt = await xero.contacts.update(
        _contact,
        { ContactID: record.xeroContactId, summarizeErrors: false });
    } else {
      rslt = await xero.contacts.create(_contact);
    }

    return new Promise((resolve, reject) => {
      if (rslt.Contacts[0].HasValidationErrors) {
        reject(new PreConditionFailedResult('412', rslt.Contacts[0].ValidationErrors[0].Message));
      } else {
        let result = { data: rslt.Contacts[0] };
        resolve(result);
      }
    });

  }

  /**
   * Function- createXeroAccount
   */
  async createXeroAccount(user, record) {
    // You can initialise Private apps directly from your configuration
    let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());
    let accountRsp = null;

    if (record.hasOwnProperty('xeroAccountCode')) {
      accountRsp = await xero.accounts.update({
        code: record.xeroAccountCode,
        Name: `${user.company} - ${user.lastName} ${user.firstName}`
      });
    } else {
      let xeroAccountCode = Date.now().toString().substring(3, 13);
      accountRsp = await xero.accounts.create({
        code: xeroAccountCode,
        Name: `${user.company} - ${user.lastName} ${user.firstName}`,
        Type: 'PREPAYMENT'
      });
    }

    return new Promise((resolve, reject) => {
      if (accountRsp.Accounts[0].HasValidationErrors) {
        reject(new PreConditionFailedResult('412', accountRsp.Accounts[0].ValidationErrors[0].Message));
      } else {
        resolve(accountRsp);
      }
    });
  }

  async getBillingInfo(record) {
    if (record.hasOwnProperty('xeroContactId')) {
      let xero = new XeroClient.AccountingAPIClient(this.getXeroConfig());

      const result = await xero.contacts.get({ ContactID: record.xeroContactId });
      result.Contacts[0]['usersCount'] = record.usersCount;
      result.Contacts[0]['planId'] = record.planId;

      return result.Contacts[0];
    } else {
      return null;
    }
  }
}
