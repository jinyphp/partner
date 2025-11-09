# AJAX를 이용한 CRUD 스크립트 사이드

## store
자바스크립트 순순한 fetch를 통하여 ajax를 생성합니다.
- form id 가 create 인 요소를 찾아서, action url의 정보를 읽어 옵니다.
- from 요소 안에 있는 `@csrf`를 참고하여 토큰을 참고 합니다.
- 오류가 있는 경우 토스트 메시지를 출력 합니다.
- 성공을 한 경우에는 이전페이지로 이동하고, 화면을 갱신하도록 합니다.

### 유효성 검사
ajax를 호출하기 위하여 유효성 검사가 필요한 경우 별도의 `valicationCheck()` 함수를 만들어 분리 관리 합니다.
먼저 ajax 를 호출하기 위하여 `valicationCheck()` 가 선언되어 있는지 확인을 하고, 선언이 되었다면 validation을 처리 합니다.


## update


## delete


### bulk-delete


