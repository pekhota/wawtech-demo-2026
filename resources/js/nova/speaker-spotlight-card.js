/*
 * Featured speakers with their photos (App\Nova\Cards\SpeakerSpotlight), for the Main dashboard.
 *
 * Plain JavaScript, no build step: Nova ships Vue with its template compiler
 * and loads this file through Nova::script(). Each speaker links to their Nova page.
 */
Nova.booting((app) => {
    app.component('speaker-spotlight-card', {
        props: ['card'],

        template: `
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow wt-spotlight">
                <div class="wt-spotlight__header">
                    <h3 class="wt-spotlight__title">Featured Speakers</h3>
                    <a :href="url('/resources/speakers')" class="wt-spotlight__all" @click.prevent="visit('/resources/speakers')">
                        All {{ card.totalSpeakers }} speakers →
                    </a>
                </div>

                <ul class="wt-spotlight__grid">
                    <li v-for="speaker in card.speakers" :key="speaker.id">
                        <a :href="url(speaker.path)" class="wt-spotlight__speaker" @click.prevent="visit(speaker.path)">
                            <span class="wt-spotlight__photo">
                                <img v-if="speaker.photo" :src="speaker.photo" alt="">
                            </span>
                            <span class="wt-spotlight__name">{{ speaker.name }}</span>
                            <span v-if="speaker.role" class="wt-spotlight__role">{{ speaker.role }}</span>
                            <span v-if="speaker.session" class="wt-stage wt-spotlight__session" :style="{ '--stage': speaker.session.color }">
                                {{ speaker.session.stage }} · {{ speaker.session.time }}
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        `,

        methods: {
            url(path) {
                return Nova.url(path)
            },

            visit(path) {
                Nova.visit(path)
            },
        },
    })
})
