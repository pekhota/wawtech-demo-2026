/*
 * Drag-and-drop ordering card (App\Nova\Cards\SortableCard), as a text list or a photo grid.
 *
 * Plain JavaScript, no build step: Nova ships Vue with its template compiler
 * and loads this file through Nova::script(). Uses native HTML drag and drop;
 * a focused item also moves with the arrow keys, Home and End.
 * Every change saves the full order to the card's saveUrl.
 */
Nova.booting((app) => {
    app.component('sortable-card', {
        props: ['card'],

        data() {
            return {
                items: [...this.card.items],
                lastSaved: [...this.card.items],
                draggingId: null,
                orderBeforeDrag: null,
                saving: false,
                saveAgain: false,
            }
        },

        created() {
            // Elements by item id, to keep keyboard focus on an item after it moves.
            this.elements = {}
        },

        template: `
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow wt-sortable" :class="'wt-sortable--' + card.layout">
                <div class="wt-sortable__header">
                    <h3 class="wt-sortable__title">{{ card.title }}</h3>
                    <p class="wt-sortable__hint">{{ card.hint }}</p>
                </div>

                <ol class="wt-sortable__items">
                    <li
                        v-for="(item, index) in items"
                        :key="item.id"
                        :ref="(element) => { elements[item.id] = element }"
                        class="wt-sortable__item"
                        :class="{ 'is-dragging': draggingId === item.id, 'is-highlighted': item.highlight }"
                        tabindex="0"
                        draggable="true"
                        :aria-label="item.label + ', position ' + (index + 1) + ' of ' + items.length"
                        @dragstart="startDrag(item, $event)"
                        @dragenter.prevent="dragOver(index)"
                        @dragover.prevent
                        @dragend="endDrag"
                        @keydown="moveWithKeyboard($event, index)"
                    >
                        <template v-if="card.layout === 'grid'">
                            <span class="wt-sortable__photo">
                                <img v-if="item.image" :src="item.image" alt="" draggable="false" loading="lazy">
                                <span class="wt-sortable__position">{{ index + 1 }}</span>
                            </span>
                            <span class="wt-sortable__label">{{ item.label }}</span>
                        </template>

                        <template v-else>
                            <span class="wt-sortable__handle" aria-hidden="true">⠿</span>
                            <span class="wt-sortable__position">{{ index + 1 }}</span>
                            <span class="wt-sortable__label">{{ item.label }}</span>
                            <button type="button" tabindex="-1" class="wt-sortable__move" :disabled="index === 0" :aria-label="'Move up: ' + item.label" @click="moveTo(index, index - 1)">↑</button>
                            <button type="button" tabindex="-1" class="wt-sortable__move" :disabled="index === items.length - 1" :aria-label="'Move down: ' + item.label" @click="moveTo(index, index + 1)">↓</button>
                        </template>
                    </li>
                </ol>
            </div>
        `,

        methods: {
            orderKey() {
                return this.items.map((item) => item.id).join()
            },

            startDrag(item, event) {
                this.draggingId = item.id
                this.orderBeforeDrag = this.orderKey()
                event.dataTransfer.effectAllowed = 'move'
                // Firefox only starts a drag when some data is set.
                event.dataTransfer.setData('text/plain', String(item.id))
            },

            // Move the dragged item live as it passes over others, like a sortable list.
            dragOver(index) {
                const from = this.items.findIndex((item) => item.id === this.draggingId)

                if (from === -1 || from === index) {
                    return
                }

                this.items.splice(index, 0, this.items.splice(from, 1)[0])
            },

            endDrag() {
                this.draggingId = null

                if (this.orderKey() !== this.orderBeforeDrag) {
                    this.save()
                }
            },

            moveWithKeyboard(event, index) {
                const targets = {
                    ArrowUp: index - 1,
                    ArrowLeft: index - 1,
                    ArrowDown: index + 1,
                    ArrowRight: index + 1,
                    Home: 0,
                    End: this.items.length - 1,
                }

                if (!(event.key in targets)) {
                    return
                }

                event.preventDefault()
                this.moveTo(index, Math.min(Math.max(targets[event.key], 0), this.items.length - 1))
            },

            moveTo(from, to) {
                if (from === to) {
                    return
                }

                const item = this.items.splice(from, 1)[0]
                this.items.splice(to, 0, item)
                this.$nextTick(() => this.elements[item.id]?.focus())
                this.save()
            },

            async save() {
                // One request at a time; a change made meanwhile is sent once the current save finishes.
                if (this.saving) {
                    this.saveAgain = true
                    return
                }

                this.saving = true

                try {
                    await Nova.request().post(this.card.saveUrl, { ids: this.items.map((item) => item.id) })
                    this.lastSaved = [...this.items]
                    Nova.success('New order saved.')
                    Nova.$emit('refresh-resources')
                } catch (error) {
                    this.items = [...this.lastSaved]
                    this.saveAgain = false

                    // Nova already shows the server's own message for a 5xx (such as the read-only demo account's).
                    if (!(error.response?.status >= 500)) {
                        Nova.error('The new order could not be saved. Reload the page and try again.')
                    }
                } finally {
                    this.saving = false

                    if (this.saveAgain) {
                        this.saveAgain = false
                        this.save()
                    }
                }
            },
        },
    })
})
