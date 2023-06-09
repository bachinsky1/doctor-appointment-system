import ModelService from "@/services/ModelService"
import axios from "axios"
import { useGlobalStateStore } from "@/stores/global"

export default class VitalSignsService extends ModelService {
    private globalStateStore

    constructor () {
        super()
        this.url = '/api/consultation'
        this.setupAPI(axios.defaults.baseURL)
        this.globalStateStore = useGlobalStateStore()
    }

    public getUnits() {
        this.globalStateStore.loadingElements['vitalSigns'] = true
        return this.get(`${this.url}/vitalsigns/units`)
            .finally(() => {
                this.globalStateStore.loadingElements['vitalSigns'] = false
            })
    }

    public getVitalSigns(patientId) {
        this.globalStateStore.loadingElements['vitalSigns'] = true
        return this.get(`${this.url}/vitalsigns/${patientId}`)
            .finally(() => {
                this.globalStateStore.loadingElements['vitalSigns'] = false
            })
    }

    public saveVitalSigns(data) {     
        this.globalStateStore.loadingElements['vitalSigns'] = true
        
        return this.post(`${this.url}/vitalsigns/store`, data)
            .finally(() => {
                this.globalStateStore.loadingElements['vitalSigns'] = false
            })
    }
}