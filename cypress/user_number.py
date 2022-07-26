from datetime import datetime
from random import randint


def generate_user_number():
    return f"{int(datetime.timestamp(datetime.now()))}{randint(100000000, 999999999)}"


if __name__ == "__main__":
    print(generate_user_number())
