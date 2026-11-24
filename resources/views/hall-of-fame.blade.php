<x-guest-layout>

    <script>
        @if(isset($stats))
            window.stats = @json($stats);
        @endif
        @if(isset($templates))
            window.templates = @json($templates);
        @endif
    </script>

    <div class="transaction-history">
        <div class="container">

 

            <div class="transaction-table-row">
                <div class="title-row">
                    <h2>Hall of Fame</h2>
                </div>
                
                <div class="leaderboard-select">
                    <select>
                        <option value="overall">Overall</option>
                        <option value="football">Football</option>
                        <option value="basketball">Basketball</option>
                        <option value="baseball">Baseball</option>
                    </select>
                </div>

                <!-- Classic Podium ------------------------------ 

                <div class="podium-stands">
                    <div class="podium-wrapper">
                        <img class="podium podium-2" src="/images/podium.svg" alt=""/>
                         <div class="team-2">
                            <img class="podium sportvatar-5" src="/images/test-character5.svg" alt=""/>
                            <img class="podium sportvatar-4" src="/images/test-character1.svg" alt=""/>
                            <img class="podium sportvatar-3" src="/images/test-character3.svg" alt=""/>
                            <img class="podium sportvatar-2" src="/images/test-character4.svg" alt=""/>
                            <img class="podium sportvatar-1" src="/images/test-character2.svg" alt=""/>
                        </div>
                        <img class="podium podium-number-2" src="/images/podium-number-2.svg" alt=""/>
                    </div>
                    <div class="podium-wrapper">
                        <img class="podium podium-1" src="/images/podium.svg" alt=""/>
                        <div class="team-1">
                            <img class="podium sportvatar-5" src="/images/test-character1.svg" alt=""/>
                            <img class="podium sportvatar-4" src="/images/test-character5.svg" alt=""/>
                            <img class="podium sportvatar-3" src="/images/test-character2.svg" alt=""/>
                            <img class="podium sportvatar-2" src="/images/test-character4.svg" alt=""/>
                            <img class="podium sportvatar-1" src="/images/test-character3.svg" alt=""/>
                        </div>
                        <img class="podium podium-number-1" src="/images/podium-number-1.svg" alt=""/>
                    </div>
                    <div class="podium-wrapper">
                        <img class="podium podium-3" src="/images/podium.svg" alt=""/>
                         <div class="team-3">
                            <img class="podium sportvatar-5" src="/images/test-character2.svg" alt=""/>
                            <img class="podium sportvatar-4" src="/images/test-character3.svg" alt=""/>
                            <img class="podium sportvatar-3" src="/images/test-character5.svg" alt=""/>
                            <img class="podium sportvatar-2" src="/images/test-character1.svg" alt=""/>
                            <img class="podium sportvatar-1" src="/images/test-character4.svg" alt=""/>
                        </div>
                        <img class="podium podium-number-3" src="/images/podium-number-3.svg" alt=""/>
                    </div>
                </div>

                --------------------------------------------------- -->

                <div class="podium-large-stands">
                    <div class="podium-large-outer">
                        <div class="podium-large-wrapper">
                            <div class="team-large-1">
                                <img class="sportvatar-large sportvatar-5" src="/images/test-character5.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-4" src="/images/test-character1.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-3" src="/images/test-character3.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-2" src="/images/test-character4.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-1" src="/images/test-character2.svg" alt=""/>
                            </div>
                            <img class="podium-large podium-large-1" src="/images/podium-large-1.svg" alt="" />
                            <svg id="team1-info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2444.04 105">
                                <text class="podium-text" transform="translate(0 85.3)"><tspan x-text="'Team Owner ID'"></tspan></text>
                                <text class="podium-text" transform="translate(2315.94 85.3)"><tspan x-text="'25.0'"></tspan></text>
                            </svg>
                        </div>
                        <div class="podium-large-wrapper">
                             <div class="team-large-2">
                                <img class="sportvatar-large sportvatar-5" src="/images/test-character3.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-4" src="/images/test-character4.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-3" src="/images/test-character2.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-2" src="/images/test-character5.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-1" src="/images/test-character1.svg" alt=""/>
                            </div>
                            <img class="podium-large podium-large-2" src="/images/podium-large-2.svg" alt="" />
                            <!--
                            <svg id="team2-info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2730.68 1689.14">
                              <text class="podium-text" transform="translate(230 230.34)"><tspan x-text="'Team Owner ID'"></tspan></text>
                              <text class="podium-text" transform="translate(2500 230.34)"><tspan x-text="'25.0'"></tspan></text>
                            </svg>
                            -->
                            <svg id="team2-info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2444.04 105">
                                <text class="podium-text" transform="translate(0 85.3)"><tspan x-text="'Team Owner ID'"></tspan></text>
                                <text class="podium-text" transform="translate(2315.94 85.3)"><tspan x-text="'25.0'"></tspan></text>
                            </svg>
                        </div>
                        <div class="podium-large-wrapper">
                            <div class="team-large-3">
                                <img class="sportvatar-large sportvatar-5" src="/images/test-character5.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-4" src="/images/test-character3.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-3" src="/images/test-character4.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-2" src="/images/test-character2.svg" alt=""/>
                                <img class="sportvatar-large sportvatar-1" src="/images/test-character1.svg" alt=""/>
                            </div>
                            <img class="podium-large podium-large-3" src="/images/podium-large-3.svg" alt="" />
                            <!--
                            <svg id="team3-info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2730.68 1689.14">
                              <text class="podium-text" transform="translate(230 230.34)"><tspan x-text="'Team Owner ID'"></tspan></text>
                              <text class="podium-text" transform="translate(2500 230.34)"><tspan x-text="'25.0'"></tspan></text>
                            </svg>
                            -->
                            <svg id="team3-info" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2444.04 105">
                                <text class="podium-text" transform="translate(0 85.3)"><tspan x-text="'Team Owner ID'"></tspan></text>
                                <text class="podium-text" transform="translate(2315.94 85.3)"><tspan x-text="'25.0'"></tspan></text>
                            </svg>
                        </div>
                    </div>
                </div>



                <div class="transaction-table-wrap">
                    <div class="scroll-x">
                        <table class="transaction-table leaderboard">
                            <tr>
                                <th class="leaderboard-rank">Rank</th>
                                <th class="leaderboard-collection">Collection</th>
                                <th class="">Owner</th>
                                <th class="leaderboard-score">Overall Score</th>
                            </tr>

                            <tr class="bg">
                                <td class="leaderboard-rank">
                                    <span>1</span>
                                </td>
                                <td class="leaderboard-collection">
                                    <div class="collection-wrapper">
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character1.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character5.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character2.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character3.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character4.svg" alt=""/>
                                        </div>
                                    </div>
                                </td>
                                <td class="leaderboard-owner">
                                    <span class="highlight">luca.find</span>
                                    <span>0x23487834878235788</span>
                                </td>
                                <td class="leaderboard-score">25.0</td>
                            </tr>

                            <tr class="">
                                <td class="leaderboard-rank">
                                    <span>2</span>
                                </td>
                                <td class="leaderboard-collection">
                                    <div class="collection-wrapper">
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character5.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character1.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character3.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character4.svg" alt=""/>
                                        </div>
                                        <div class="collection-sportvatar">
                                            <img src="/images/test-character2.svg" alt=""/>
                                        </div>
                                    </div>
                                </td>
                                <td class="leaderboard-owner">
                                    <span>0x23487834878235788</span>
                                </td>
                                <td class="leaderboard-score">25.0</td>
                            </tr>
  
                        </table>
                    </div>
                </div>
                {{-- no list component on this (static preview) page: the shared pagination partial needs nextPage/loadNextPage --}}
            </div>
        </div>
    </div>

    @include('partials/powered-by')
</x-guest-layout>
