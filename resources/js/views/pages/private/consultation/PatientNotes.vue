<template>
    <div class="rounded-lg bg-white shadow-lg mb-3 mr-3">
        <div class="bg-white rounded-t-lg p-4 flex justify-between items-center">
            <h2 class="text-base font-semibold leading-7 text-gray-900">
                <button v-if="!showEditor" class="text-blue-500 hover:text-blue-700 focus:outline-none ml-2">
                    <i class="fas fa-heartbeat"></i>
                </button>
                <button v-if="showEditor" @click="showEditor = false; editingIndex = -1" class="text-blue-500 hover:text-blue-700 focus:outline-none ml-2">
                    <i class="fas fa-arrow-left"></i>
                </button> Patient notes
            </h2>
        </div>
        <div v-if="!showEditor"  id="patientnotes" class="border-t border-b border-gray-200 max-h-30vh overflow-auto p-3">
            <div v-if="notes.length > 0">
                <ul class="list-none">
                    <li v-for="(item, index) in notes" :key="index" class="flex items-start justify-between border-b py-2 border-gray-300 last:border-b-0">
                        <div class="note-content flex-1 mr-2" v-html="item.note" v-tippy="{ content: item.note, placement: 'right' }"></div>
                        <div class="buttons ml-auto items-start">
                            <button v-on:click.stop="editNote(item.note, index)" class="text-blue-500 hover:text-blue-700 focus:outline-none">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="removeNote(index)" class="text-red-500 hover:text-red-700 focus:outline-none  ml-2">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="flex justify-center items-center">No notes here</div>
        </div>
        <div v-if="showEditor" class="border-t border-b border-gray-200 max-h-30vh overflow-auto p-3">
            <div class="grid grid-cols-1 sm:grid-cols-1">
                <TextEditor v-model="editorContent" />
            </div>
        </div>
        <div class="bg-white rounded-b-lg p-4 flex justify-center items-center">
            <div v-if="!showEditor">
                <button @click="showEditor = true; clearEditor()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded">
                    <i class="fas fa-plus-circle"></i> Add note </button>
            </div>
            <button v-if="showEditor" @click="showEditor = false; editingIndex = -1" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded mr-2">
                <i class="fas fa-times-circle"></i> Cancel </button>
            <button v-if="showEditor" @click="saveNote" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-2 rounded">
                <i class="fas fa-save"></i> Save note </button>
        </div>
        <BlockAlert :show="show" :type="type" :message="message" :description="description" @close="show = false" ref="alert" />
    </div>
</template>

<script>
import { trans } from "@/helpers/i18n"
import { getResponseError } from "@/helpers/api"
import { useConsultationStore } from "@/stores"
import { useAlertStore } from "@/stores"
import PatientNoteService from "@/services/PatientNoteService"
import TextEditor from "@/views/components/input/TextEditor"
import BlockAlert from '@/views/components/BlockAlert.vue'

export default {
    name: "PatientNotes",

    props: {
    },

    components: {
        TextEditor,
        BlockAlert
    },

    data() {
        return {
            showEditor: false,
            notes: [],
            editorContent: '',
            editingIndex: -1,
            show: false,
            type: '',
            message: '',
            description: ''
        };
    },

    async mounted() {
        try {
            setTimeout(async () => {
                const consultation = this.consultationStore.currentConsultation
                const result = await this.patientNoteService.getPatientNotes(consultation.public_id)
                this.notes = result.data
            })
        } catch (error) {
            console.error(error)
        }
    },

    methods: {
        showSuccess(message, description) {
            const alert = {
                type: 'success',
                message: message || 'Success!',
                description: description,
                show: true
            }

            this.$refs.alert.addAlert(alert)
        },

        showError(message, description) {
            const alert = {
                type: 'error',
                message: message || 'Error!',
                description: description,
                show: true
            }

            this.$refs.alert.addAlert(alert)
        },

        clearEditor() {
            this.editorContent = ''
        },
        editNote(note, index) {
            this.editorContent = note
            this.showEditor = true
            this.editingIndex = index
        },

        async saveNote() {
            const newNote = this.editorContent
            if (newNote) {

                try {
                    if (this.editingIndex >= 0) {

                        const note = this.notes[this.editingIndex]
                        const data = {
                            public_id: this.consultationStore.currentConsultation.public_id,
                            note: newNote,
                            note_id: note.id
                        }

                        const result = await this.patientNoteService.patchPatientNote(data)
                        if (result.data) {
                            this.notes.splice(this.editingIndex, 1, result.data)
                        }
                    } else {
                        const data = {
                            public_id: this.consultationStore.currentConsultation.public_id,
                            note: newNote
                        }
                        const result = await this.patientNoteService.storePatientNote(data)
                        const note = result.data
                        this.notes.push(note)
                    }
                    this.showEditor = false
                    this.editorContent = ''
                    this.editingIndex = -1
                    this.showSuccess('Success!', 'Note saved successfully')
                } catch (error) {
                    const { message, description } = getResponseError(error)
                    this.showError(message, description)
                }

                
            }
        },
        async removeNote(index) {
            if (confirm('Are you sure you want to delete this note?')) {
                const data = {
                    public_id: this.consultationStore.currentConsultation.public_id,
                    note_id: this.notes[index].id
                }

                try {
                    const result = await this.patientNoteService.deletePatientNote(data)

                    if (result.data) {
                        this.notes.splice(index, 1)
                        this.showSuccess('Success!', 'Note deleted successfully')
                    }
                } catch (error) {
                    const { message, description } = getResponseError(error)
                    this.showError(message, description)
                }
                
            }
        },
    },

    setup() {

        const consultationStore = useConsultationStore()
        const alertStore = useAlertStore()
        const patientNoteService = new PatientNoteService()

        return {
            consultationStore,
            alertStore,
            patientNoteService
        }
    }

}
</script> 