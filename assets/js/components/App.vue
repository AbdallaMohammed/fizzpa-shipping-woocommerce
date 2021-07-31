<template>
    <div>
        <div v-if="enabled">
            <div v-if="!loading">
                <button type="button" class="button-primary" @click="showModal" v-if="shipment === null">
                    Prepare Fizzpa Shipment
                </button>
                <button type="button" class="button-primary" @click="showModal" v-if="shipment !== null">
                    Shipment Details
                </button>
                <button type="button" class="button-primary" @click="showTrackModal" v-if="shipment !== null">
                    Track Order
                </button>
                <button type="button" class="button-primary" style="margin-top: 0.5rem" @click="showPrintModal" v-if="shipment !== null">
                    Print Order
                </button>
            </div>
            <Spinner v-if="loading"></Spinner>
            <modal name="shipment-modal" height="auto" :scrollable="true" :clickToClose="clickToClose">
                <div class="fizzpa-modal-dialog" v-if="clickToClose">
                    <div class="fizzpa-modal-content">
                        <div class="fizzpa-modal-header">
                            <div class="modal-header-left">
                                Fizzpa
                            </div>
                        </div>
                        <div class="fizzpa-modal-body" v-if="shipment === null">
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Sender Name</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.SenderName">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Sender Phone</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.SenderPhone">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Recipient City ID</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.RecipientCityId">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Pickup Address ID</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.PickupAddressId">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Recipient Address</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.RecipientAddress">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Recipient Name</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <input type="text" v-model="form.RecipientName">
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Notes</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <textarea v-model="form.OrderNote"></textarea>
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <button type="button" @click="createOrder" class="button-primary">Submit</button>
                            </div>
                        </div>
                        <div class="fizzpa-modal-body" v-if="shipment !== null">
                            <p>
                                <strong>Order ID</strong>: <i>{{ shipment.OrderId }}</i>
                                <br>
                                <strong>Order Date</strong>: <i>{{ shipment.OrderDate }}</i>
                                <br>
                                <strong>Due Date</strong>: <i>{{ shipment.DueDate }}</i>
                                <br>
                                <strong>Agent Confirmation Type</strong>: <i>{{ shipment.AgentConfirmationType_Ar }}</i>
                                <br>
                                <strong>Agent Confirmation Date</strong>: <i>{{ shipment.AgentConfirmationDate }}</i>
                                <br>
                                <strong>Company Confirmation Type</strong>: <i>{{ shipment.CompanyConfirmationType_Ar }}</i>
                                <br>
                                <strong>Company Confirmation Date</strong>: <i>{{ shipment.CompanyConfirmationDate }}</i>
                                <br>
                                <strong>Delivered?</strong>: <i>{{ shipment.IsDelivered ? 'Yes' : 'No' }}</i>
                                <br>
                                <strong>Delivery Fees</strong>: <i>{{ shipment.DeliveryFees }}</i>
                                <br>
                                <strong>Delivery Tax</strong>: <i>{{ shipment.DeliveryTax }}</i>
                                <br>
                                <strong>Delivery Total</strong>: <i>{{ shipment.DeliveryTotal }}</i>
                                <br>
                                <strong>Agent Sales Total</strong>: <i>{{ shipment.AgentSalesTotal }}</i>
                                <br>
                                <strong>Agent Sales Tax</strong>: <i>{{ shipment.AgentSalesTax }}</i>
                                <br>
                                <strong>Agent Total</strong>: <i>{{ shipment.AgentTotal }}</i>
                            </p>
                        </div>
                    </div>
                </div>
                <div style="padding: 15px" v-else>
                    <Spinner></Spinner>
                </div>
            </modal>
            <modal name="track-modal" height="auto" :scrollable="true">
                <div class="fizzpa-modal-dialog" v-if="shipment !== null">
                    <div class="fizzpa-modal-content">
                        <div class="fizzpa-modal-header">
                            <div class="modal-header-left">
                                Track Order No. {{ shipment.OrderId }}
                            </div>
                        </div>
                        <div class="fizzpa-modal-body" v-if="tracking.length > 0">
                            <div class="fizzpa-panel" v-for="track in tracking" :key="track.Trackid">
                                <div class="fizzpa-panel-body">
                                    {{ track.WhereIN }} - <small>{{ track.TrackingDate }}</small>
                                </div>
                                <div class="fizzpa-panel-footer">
                                    {{ track.Note }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </modal>
            <modal name="print-order" height="auto" :scrollable="true">
                <div class="fizzpa-modal-dialog" v-if="shipment !== null">
                    <div class="fizzpa-modal-content">
                        <div class="fizzpa-modal-header">
                            <div class="modal-header-left">
                                Print Order No. {{ shipment.OrderId }}
                            </div>
                        </div>
                        <div class="fizzpa-modal-body">
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Language</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <select v-model="print.language">
                                        <option value="ar">Arabic</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <div class="fizzpa-modal-field-label">
                                    <label>Size</label>
                                </div>
                                <div class="fizzpa-modal-field">
                                    <select v-model="print.size">
                                        <option value="A4">A4</option>
                                        <option value="A6">A6</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fizzpa-modal-form-row">
                                <button type="button" @click="printOrder" class="button-primary">Submit</button>
                            </div>
                            <iframe v-if="iframeUrl !== ''" :src="iframeUrl" style="width: 100%"></iframe>
                        </div>
                    </div>
                </div>
            </modal>
        </div>
    </div>
</template>

<script>
import Spinner from 'vue-simple-spinner'

export default {
    data() {
        return {
            enabled: true,
            loading: false,
            settings: {},
            fizzpa_i18n: fizzpa_i18n,
            clickToClose: true,
            form: {},
            shipment: null,
            tracking: [],
            print: {
                language: 'en',
                size: 'A4',
            },
            iframeUrl: '',
        }
    },
    components: {
        Spinner,
    },
    async created() {
        await this.getData()
    },
    methods: {
        async getData() {
            this.loading = true

            const urlParams = new URLSearchParams(window.location.search)

            const response = await this.axios.get(fizzpa_i18n.admin_ajax, {
                params: {
                    action: 'fizzpa_get_shipment',
                    order_id: urlParams.get('post'),
                    nonce: fizzpa_i18n.nonce
                }
            })

            if (Array.isArray(response.data.data)) {
                this.shipment = response.data.data[0]

                const res = await this.axios.get(fizzpa_i18n.admin_ajax, {
                    params: {
                        action: 'fizzpa_tracking_order',
                        order_id: urlParams.get('post'),
                        nonce: fizzpa_i18n.nonce
                    }
                })

                this.tracking = res.data.data
            } else {
                const { data } = await this.axios.get(fizzpa_i18n.admin_ajax, {
                    params: {
                        action: 'fizzpa_get_order_settings',
                        order_id: urlParams.get('post'),
                        nonce: fizzpa_i18n.nonce,
                    }
                })

                this.form = data.data
                this.form.action = 'fizzpa_shipment'
                this.form.nonce = this.fizzpa_i18n.nonce
            }

            this.loading = false
        },
        showModal() {
            this.$modal.show('shipment-modal')
        },
        showTrackModal() {
            this.$modal.show('track-modal')
        },
        showPrintModal() {
            this.$modal.show('print-order')
        },
        async createOrder() {
            this.clickToClose = false

            const { data } = await this.axios.get(fizzpa_i18n.admin_ajax, {
                params: this.form
            }).finally(() => {
                this.clickToClose = true
            })

            if (! data.data.success) {
                this.$toast.error(data.data.message)
            } else {
                this.$toast.success(data.data.message)
                this.$modal.hide('shipment-modal')
                
                await this.getData()
            }
        },
        async printOrder() {
            const urlParams = new URLSearchParams(window.location.search)

            const { data } = await this.axios.get(fizzpa_i18n.admin_ajax, {
                params: {
                    action: 'fizzpa_print_order',
                    order_id: urlParams.get('post'),
                    nonce: fizzpa_i18n.nonce,
                    size: this.print.size,
                    language: this.print.language,
                }
            })

            this.iframeUrl = data.data.url
        },
    }
}
</script>

<style>
.woocommerce-layout__header {
    z-index: 999 !important;
}
</style>