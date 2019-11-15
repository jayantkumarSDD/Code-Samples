/**
 * pass function to controller.
 * @preferred
 */
import { ApiHandler } from '../../shared/api.interfaces';
import { BillingController } from './billing.controller';
import { BillingService } from './billing.service';

const service: BillingService = new BillingService();
const controller: BillingController = new BillingController(service);

export const getItems: ApiHandler = controller.getItems;
export const getItem: ApiHandler = controller.getItem;
export const createXeroContact: ApiHandler = controller.createXeroContact;
export const getBillingInfo: ApiHandler = controller.getBillingInfo;
